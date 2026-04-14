<?php
// lib/DatabaseClient.php
require_once __DIR__ . '/../config/database.php';

class DatabaseClient {
    /** @var PDO */
    private $pdo;

    public function __construct($role = 'anon') {
        $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
        $port = defined('DB_PORT') ? ltrim(getenv('DB_PORT'), '"') : '5432';
        $dbname = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
        $user = defined('DB_USER') ? DB_USER : getenv('DB_USER');
        $pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');
        // fallback to port if quotes were an issue
        $port = trim($port, "'\"");

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new Exception("Lỗi kết nối CSDL: " . $e->getMessage());
        }
    }

    // Auth functions fake-out (re-coded for native users table)
    public function signUp($email, $password) {
        try {
            $check = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
            $check->execute(['email' => $email]);
            if ($check->rowCount() > 0) {
                return ['code' => 400, 'data' => ['error_description' => 'Email đã tồn tại.']];
            }

            $stmt = $this->pdo->prepare("INSERT INTO users (email, password_hash) VALUES (:email, :pass) RETURNING id");
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt->execute(['email' => $email, 'pass' => $hash]);
            $user = $stmt->fetch();
            
            return ['code' => 200, 'data' => ['user' => ['id' => $user['id'], 'email' => $email]]];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function signIn($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Giả mạo token giống cấu trúc Supabase (dùng Session ở client)
                $accessToken = bin2hex(random_bytes(32)); 
                return [
                    'code' => 200, 
                    'data' => [
                        'access_token' => $accessToken,
                        'user' => [
                            'id' => $user['id'], 
                            'email' => $user['email']
                        ]
                    ]
                ];
            }
            return ['code' => 400, 'data' => ['error_description' => 'Tài khoản hoặc mật khẩu không đúng.']];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function updateAuthUser($userId, $data) {
        return $this->update('users', 'id', $userId, $data);
    }

    /**
     * Dịch truy vấn PostgREST (VD: select=id,name&id=eq.123&order=id.asc)
     */
    private function parsePostgREST($table, $queryStr) {
        $select = '*';
        $where = [];
        $params = [];
        $orderBy = '';
        $limit = '';

        if (!empty($queryStr)) {
            parse_str($queryStr, $parsed);
            
            if (isset($parsed['select'])) {
                $selectObj = $parsed['select'];
                unset($parsed['select']);
                
                $joinSql = "";
                $selectFields = ["$table.*"];
                
                // --- Hardcoded Join Handler for PostgREST syntax ---
                if ($table === 'majors' && strpos($selectObj, 'education_levels(name)') !== false) {
                    $joinSql .= " LEFT JOIN education_levels ON $table.education_level_id = education_levels.id";
                    $selectFields[] = "education_levels.name as education_levels__name";
                }
                if ($table === 'admission_periods' && strpos($selectObj, 'education_levels(name)') !== false) {
                    $joinSql .= " LEFT JOIN education_levels ON $table.education_level_id = education_levels.id";
                    $selectFields[] = "education_levels.name as education_levels__name";
                }
                if ($table === 'user_documents' && strpos($selectObj, 'document_types(type_name)') !== false) {
                    $joinSql .= " LEFT JOIN document_types ON $table.document_type_id = document_types.id";
                    $selectFields[] = "document_types.type_name as document_types__type_name";
                }
                if ($table === 'applications') {
                    if (strpos($selectObj, 'admission_periods(name)') !== false) {
                        $joinSql .= " LEFT JOIN admission_periods ON applications.admission_period_id = admission_periods.id";
                        $selectFields[] = "admission_periods.name as admission_periods__name";
                    }
                    if (strpos($selectObj, 'majors(major_name,zalo_link,education_levels(name))') !== false || strpos($selectObj, 'majors(major_name,education_levels(name))') !== false) {
                        $joinSql .= " LEFT JOIN majors ON applications.major_id = majors.id";
                        $joinSql .= " LEFT JOIN education_levels ON majors.education_level_id = education_levels.id";
                        $selectFields[] = "majors.major_name as majors__major_name";
                        if (strpos($selectObj, 'zalo_link') !== false) {
                            $selectFields[] = "majors.zalo_link as majors__zalo_link";
                        }
                        $selectFields[] = "education_levels.name as majors__education_levels__name";
                    } elseif (strpos($selectObj, 'majors(major_name)') !== false) {
                        $joinSql .= " LEFT JOIN majors ON applications.major_id = majors.id";
                        $selectFields[] = "majors.major_name as majors__major_name";
                    }
                    if (strpos($selectObj, 'admission_methods(method_name,application_fee)') !== false) {
                        $joinSql .= " LEFT JOIN admission_methods ON applications.admission_method_id = admission_methods.id";
                        $selectFields[] = "admission_methods.method_name as admission_methods__method_name";
                        $selectFields[] = "admission_methods.application_fee as admission_methods__application_fee";
                    }
                }
                
                if (!empty($joinSql)) {
                    $select = implode(', ', $selectFields);
                    return [
                        'sql' => "SELECT $select FROM $table $joinSql",
                        'params' => [],
                        'is_join' => true,
                        '_parsed' => $parsed
                    ];
                } else {
                    $select = $selectObj;
                }
            }
            if (isset($parsed['order'])) {
                // order=id.asc -> ORDER BY id asc
                list($sortField, $sortDir) = explode('.', $parsed['order']);
                $orderBy = "ORDER BY $sortField " . strtoupper($sortDir);
                unset($parsed['order']);
            }
            if (isset($parsed['limit'])) {
                $limit = "LIMIT " . intval($parsed['limit']);
                unset($parsed['limit']);
            }

            // Mọi tham số còn lại coi như là WHERE (id=eq.123, status=in.(PENDING,APPROVED), is_active=eq.true)
            $paramIndex = 1;
            foreach ($parsed as $key => $val) {
                if (preg_match('/^eq\.(.*)$/', $val, $match)) {
                    $valToBind = $match[1];
                    if ($valToBind === 'true') $valToBind = true;
                    if ($valToBind === 'false') $valToBind = false;

                    $where[] = "$key = :p" . $paramIndex;
                    $params["p" . $paramIndex] = $valToBind;
                    $paramIndex++;
                }
                elseif (preg_match('/^gte\.(.*)$/', $val, $match)) {
                    $where[] = "$key >= :p" . $paramIndex;
                    $params["p" . $paramIndex] = $match[1];
                    $paramIndex++;
                }
                elseif (preg_match('/^in\.\((.*)\)$/', $val, $match)) {
                    // in.(1,2,3)
                    $inVals = explode(',', $match[1]);
                    $inPlaceholders = [];
                    foreach ($inVals as $v) {
                        $pName = "p" . $paramIndex++;
                        $inPlaceholders[] = ":" . $pName;
                        $params[$pName] = trim($v, '"\'');
                    }
                    $where[] = "$key IN (" . implode(',', $inPlaceholders) . ")";
                }
                // Các toán tử khác có thể tự add thêm nếu cần
            }
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        return [
            'sql' => "SELECT $select FROM $table $whereSql $orderBy $limit",
            'params' => $params,
            'is_join' => false
        ];
    }

    public function select($table, $query = '', $token = null) {
        try {
            $parsed = $this->parsePostgREST($table, $query);
            
            // If the query was a JOIN intercepted above, we need to apply the WHERE, ORDER BY, LIMIT manually to the joined output
            if ($parsed['is_join'] ?? false) {
                $baseSql = $parsed['sql'];
                $_parsedRes = $this->parsePostgREST($table, http_build_query($parsed['_parsed']));
                // Extract WHERE, ORDER BY, LIMIT from the second pass
                $suffix = str_replace("SELECT * FROM $table ", "", $_parsedRes['sql']);
                if (trim($suffix) === ("SELECT * FROM $table")) $suffix = "";
                $parsed['sql'] = $baseSql . " " . $suffix;
                $parsed['params'] = $_parsedRes['params'];
            }
            
            $stmt = $this->pdo->prepare($parsed['sql']);
            $stmt->execute($parsed['params']);
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($data as $row) {
                $item = [];
                foreach ($row as $k => $v) {
                    if (strpos($k, '__') !== false) {
                        $parts = explode('__', $k);
                        $ptr = &$item;
                        foreach ($parts as $i => $part) {
                            if ($i === count($parts) - 1) $ptr[$part] = $v;
                            else { if (!isset($ptr[$part])) $ptr[$part] = []; $ptr = &$ptr[$part]; }
                        }
                    } else {
                        $item[$k] = $v;
                    }
                }
                $result[] = $item;
            }
            return ['code' => 200, 'data' => $result];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function rawQuery($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            // Re-structure the array recursively to simulate Supabase nested JSON structure
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($data as $row) {
                $item = [];
                foreach ($row as $k => $v) {
                    if (strpos($k, '__') !== false) {
                        // Reconstruct nested object (e.g. education_levels__name -> ['education_levels']['name'])
                        $parts = explode('__', $k);
                        $ptr = &$item;
                        foreach ($parts as $i => $part) {
                            if ($i === count($parts) - 1) {
                                $ptr[$part] = $v;
                            } else {
                                if (!isset($ptr[$part])) $ptr[$part] = [];
                                $ptr = &$ptr[$part];
                            }
                        }
                    } else {
                        $item[$k] = $v;
                    }
                }
                $result[] = $item;
            }
            return ['code' => 200, 'data' => $result];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function insert($table, $data, $token = null) {
        try {
            $keys = array_keys($data);
            $fields = implode(', ', $keys);
            $placeholders = ':' . implode(', :', $keys);
            
            $stmt = $this->pdo->prepare("INSERT INTO $table ($fields) VALUES ($placeholders)");
            $stmt->execute($data);
            // Giả lập trả về dòng vừa insert (cần fetch lại hoặc coi như thành công)
            return ['code' => 201, 'data' => [$data]];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function upsert($table, $data, $token = null) {
        try {
            if (empty($data)) return ['code' => 400, 'error' => 'No data'];
            
            $keys = array_keys($data);
            $fields = implode(', ', $keys);
            $placeholders = ':' . implode(', :', $keys);
            
            $updateSet = [];
            foreach ($keys as $k) {
                if ($k !== 'id') {
                    $updateSet[] = "$k = EXCLUDED.$k";
                }
            }
            $updateSql = implode(', ', $updateSet);
            $conflictKey = 'id'; // default
            if(isset($data['period_id']) && isset($data['major_id'])) $conflictKey = "period_id, major_id"; // exception for junction tables
            if(isset($data['period_id']) && isset($data['major_id']) && isset($data['method_id'])) $conflictKey = "period_id, major_id, method_id";
            
            // PostgreSQL ON CONFLICT clause
            $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
            if (!empty($updateSql)) {
                $sql .= " ON CONFLICT ($conflictKey) DO UPDATE SET $updateSql";
            } else {
                $sql .= " ON CONFLICT ($conflictKey) DO NOTHING";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return ['code' => 201, 'data' => [$data]];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function update($table, $matchField, $matchValue, $data, $token = null) {
        try {
            $set = [];
            foreach ($data as $k => $v) {
                $set[] = "$k = :$k";
            }
            $setSql = implode(', ', $set);

            $sql = "UPDATE $table SET $setSql WHERE $matchField = :_match";
            $data['_match'] = $matchValue;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return ['code' => 200, 'data' => []];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function delete($table, $matchField, $matchValue, $token = null) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM $table WHERE $matchField = :m");
            $stmt->execute(['m' => $matchValue]);
            return ['code' => 200, 'data' => []];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function updateBulk($table, $matchField, array $ids, $data, $token = null) {
        if (empty($ids)) return ['code' => 400, 'data' => ['error' => 'No IDs provided']];
        try {
            $set = [];
            foreach ($data as $k => $v) {
                $set[] = "$k = :$k";
            }
            $setSql = implode(', ', $set);

            $inPlaceholders = [];
            $params = $data;
            foreach ($ids as $i => $id) {
                $inPlaceholders[] = ":id$i";
                $params["id$i"] = $id;
            }
            $inSql = implode(',', $inPlaceholders);

            $sql = "UPDATE $table SET $setSql WHERE $matchField IN ($inSql)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ['code' => 200, 'data' => []];
        } catch (Exception $e) {
            return ['code' => 500, 'error' => $e->getMessage()];
        }
    }

    public function count($table, $query = '') {
        try {
            $parsed = $this->parsePostgREST($table, $query);
            // Replace select with count
            $sql = preg_replace('/SELECT .*? FROM/i', 'SELECT COUNT(*) as count FROM', $parsed['sql']);
            // Remove limits/orders
            $sql = preg_replace('/ORDER BY.*/i', '', $sql);
            $sql = preg_replace('/LIMIT.*/i', '', $sql);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parsed['params']);
            $res = $stmt->fetch();
            return (int)($res['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function buildInList(array $ids): string {
        return implode(',', array_map(function($id) { return '"' . $id . '"'; }, $ids));
    }

    public function fetchUserProfilesMap(array $userIds, string $select = 'id,full_name,identity_card,phone_number'): array {
        $map = [];
        $userIds = array_values(array_unique(array_filter($userIds)));
        if (empty($userIds)) return $map;

        $chunks = array_chunk($userIds, 100);
        foreach ($chunks as $chunk) {
            $inList = self::buildInList($chunk);
            $query = "select={$select}&id=in.({$inList})";
            $res = $this->select('user_profiles', $query);

            if ($res['code'] == 200 && is_array($res['data'])) {
                foreach ($res['data'] as $u) {
                    $map[$u['id']] = $u;
                }
            }
        }
        return $map;
    }
}
