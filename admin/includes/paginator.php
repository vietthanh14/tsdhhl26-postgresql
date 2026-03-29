<?php
/**
 * Paginator Component — admin/includes/paginator.php
 * 
 * Shared across: users.php, applications.php, documents.php
 * 
 * Required variables (must be set before include):
 *   $page       (int)   — Current page number
 *   $totalPages (int)   — Total number of pages
 *   $queryParams (array) — Associative array of extra query string params to preserve
 *                          Example: ['search' => 'Nguyễn', 'status' => 'PENDING']
 */

if (!isset($queryParams) || !is_array($queryParams)) {
    $queryParams = [];
}

// Build query string from non-empty params
$qs = '';
foreach ($queryParams as $key => $val) {
    if ($val !== '' && $val !== null) {
        $qs .= '&' . urlencode($key) . '=' . urlencode($val);
    }
}

if ($totalPages > 1):
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
?>
<nav class="mt-4" aria-label="Page navigation">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <!-- Previous Page -->
        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; echo $qs; ?>">Trước</a>
        </li>
        
        <?php 
        if ($startPage > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1'.$qs.'">1</a></li>';
            if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        for ($i = $startPage; $i <= $endPage; $i++): 
        ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; echo $qs; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; 
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo '<li class="page-item"><a class="page-link" href="?page='.$totalPages.$qs.'">'.$totalPages.'</a></li>';
        }
        ?>
        
        <!-- Next Page -->
        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; echo $qs; ?>">Sau</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
