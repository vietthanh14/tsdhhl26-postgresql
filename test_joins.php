<?php
require __DIR__ . '/lib/SupabaseClient.php';
$client = new SupabaseClient('service');
$query = 'select=*,admission_periods(name),majors(major_name),admission_methods(method_name)&order=submitted_at.desc';
$res = $client->select('applications', $query);
var_dump($res);
