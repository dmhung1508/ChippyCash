<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check login
if (!isset($_SESSION['user_id'])) {
   redirectTo('index.php');
}

$user_id = $_SESSION['user_id'];

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $action = $_POST['action'] ?? '';
   
   // Add category
   if ($action === 'add') {
       $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
       $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
       $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
       
       if (empty($name)) {
           setFlashMessage('error', 'Tên thể loại không được để trống');
       } elseif (empty($type) || !in_array($type, ['income', 'expense'])) {
           setFlashMessage('error', 'Loại giao dịch không hợp lệ');
       } elseif (isCategoryNameExists($conn, $user_id, $name, $type)) {
           setFlashMessage('error', 'Tên thể loại đã tồn tại cho loại giao dịch này');
       } else {
           if (addCategory($conn, $user_id, $name, $description, $type)) {
               setFlashMessage('success', 'Thêm thể loại thành công!');
           } else {
               setFlashMessage('error', 'Đã xảy ra lỗi khi thêm thể loại.');
           }
       }
   }
   
   // Update category
   if ($action === 'update') {
       $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
       $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
       $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
       $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
       
       $category = getCategoryById($conn, $category_id, $user_id);
       
       if (!$category) {
           setFlashMessage('error', 'Thể loại không tồn tại.');
       } elseif (empty($name)) {
           setFlashMessage('error', 'Tên thể loại không được để trống');
       } elseif (empty($type) || !in_array($type, ['income', 'expense'])) {
           setFlashMessage('error', 'Loại giao dịch không hợp lệ');
       } elseif ($name !== $category['name'] && isCategoryNameExists($conn, $user_id, $name, $type, $category_id)) {
           setFlashMessage('error', 'Tên thể loại đã tồn tại cho loại giao dịch này');
       } else {
           if (updateCategory($conn, $category_id, $user_id, $name, $description, $type)) {
               setFlashMessage('success', 'Cập nhật thể loại thành công!');
           } else {
               setFlashMessage('error', 'Đã xảy ra lỗi khi cập nhật thể loại.');
           }
       }
   }
   
   // Redirect to avoid form resubmission
   redirectTo('categories.php');
}

// Process delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
   $category_id = $_GET['delete'];
   $category = getCategoryById($conn, $category_id, $user_id);
   
   if ($category) {
       $usage_count = getCategoryUsageCount($conn, $user_id, $category['name']);
       
       if ($usage_count > 0) {
           setFlashMessage('error', 'Không thể xóa thể loại đang được sử dụng trong ' . $usage_count . ' giao dịch.');
       } else {
           if (deleteCategory($conn, $category_id, $user_id)) {
               setFlashMessage('success', 'Xóa thể loại thành công!');
           } else {
               setFlashMessage('error', 'Đã xảy ra lỗi khi xóa thể loại.');
           }
       }
   } else {
       setFlashMessage('error', 'Thể loại không tồn tại.');
   }
   
   // Redirect to avoid repeated deletions
   redirectTo('categories.php');
}

// Get categories
$income_categories = getUserCategories($conn, $user_id, 'income');
$expense_categories = getUserCategories($conn, $user_id, 'expense');

$pageTitle = "Quản lý thể loại";
include 'includes/header.php';
?>

<div class="app-container">
   <header class="page-header">
       <div class="header-content">
           <div class="header-left">
               <h1>Quản lý thể loại</h1>
               <p class="subtitle">Tạo và quản lý các thể loại thu chi của bạn</p>
           </div>
           <div class="header-right">
               <a href="index.php" class="btn-link">
                   <i class="fas fa-arrow-left"></i> Quay lại
               </a>
               <button id="addCategoryBtn" class="btn-primary">
                   <i class="fas fa-plus"></i>
                   Thêm thể loại
               </button>
           </div>
       </div>
   </header>

   <main class="main-content">
       <?php echo displayFlashMessages(); ?>
       
       <div class="content-tabs">
           <nav class="tab-nav">
               <button class="tab-button active" data-tab="income-categories">Thể loại thu nhập</button>
               <button class="tab-button" data-tab="expense-categories">Thể loại chi tiêu</button>
           </nav>
           
           <div class="tab-content">
               <!-- Tab Thể loại thu nhập -->
               <div id="income-categories" class="tab-pane active">
                   <div class="section-header">
                       <h2>Thể loại thu nhập</h2>
                   </div>
                   
                   <?php if (count($income_categories) > 0): ?>
                       <div class="categories-grid">
                           <?php foreach ($income_categories as $category): ?>
                               <?php $usage_count = getCategoryUsageCount($conn, $user_id, $category['name']); ?>
                               <div class="category-card" data-id="<?php echo $category['id']; ?>">
                                   <div class="category-header">
                                       <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                       <div class="category-actions">
                                           <button class="btn-icon edit edit-category-btn" title="Chỉnh sửa" data-id="<?php echo $category['id']; ?>">
                                               <i class="fas fa-edit"></i>
                                           </button>
                                           <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa thể loại này?');">
                                               <i class="fas fa-trash"></i>
                                           </a>
                                       </div>
                                   </div>
                                   <div class="category-description">
                                       <?php echo htmlspecialchars($category['description'] ?: 'Không có mô tả'); ?>
                                   </div>
                                   <div class="category-meta">
                                       <span class="category-date">Tạo ngày: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?></span>
                                       <?php if ($usage_count > 0): ?>
                                           <span class="category-usage">Đã dùng: <?php echo $usage_count; ?> lần</span>
                                       <?php endif; ?>
                                   </div>
                                   <input type="hidden" class="category-data" 
                                       data-id="<?php echo $category['id']; ?>"
                                       data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                       data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
                                       data-type="<?php echo $category['type']; ?>"
                                       data-usage="<?php echo $usage_count; ?>">
                               </div>
                           <?php endforeach; ?>
                       </div>
                   <?php else: ?>
                       <div class="empty-state">
                           <div class="empty-icon">
                               <i class="fas fa-tags"></i>
                           </div>
                           <h3>Chưa có thể loại thu nhập nào</h3>
                           <p>Hãy thêm thể loại thu nhập để phân loại các khoản thu của bạn.</p>
                           <button id="emptyAddIncomeCategoryBtn" class="btn-primary">
                               <i class="fas fa-plus"></i> Thêm thể loại thu nhập
                           </button>
                       </div>
                   <?php endif; ?>
               </div>
               
               <!-- Tab Thể loại chi tiêu -->
               <div id="expense-categories" class="tab-pane">
                   <div class="section-header">
                       <h2>Thể loại chi tiêu</h2>
                   </div>
                   
                   <?php if (count($expense_categories) > 0): ?>
                       <div class="categories-grid">
                           <?php foreach ($expense_categories as $category): ?>
                               <?php $usage_count = getCategoryUsageCount($conn, $user_id, $category['name']); ?>
                               <div class="category-card" data-id="<?php echo $category['id']; ?>">
                                   <div class="category-header">
                                       <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                       <div class="category-actions">
                                           <button class="btn-icon edit edit-category-btn" title="Chỉnh sửa" data-id="<?php echo $category['id']; ?>">
                                               <i class="fas fa-edit"></i>
                                           </button>
                                           <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa thể loại này?');">
                                               <i class="fas fa-trash"></i>
                                           </a>
                                       </div>
                                   </div>
                                   <div class="category-description">
                                       <?php echo htmlspecialchars($category['description'] ?: 'Không có mô tả'); ?>
                                   </div>
                                   <div class="category-meta">
                                       <span class="category-date">Tạo ngày: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?></span>
                                       <?php if ($usage_count > 0): ?>
                                           <span class="category-usage">Đã dùng: <?php echo $usage_count; ?> lần</span>
                                       <?php endif; ?>
                                   </div>
                                   <input type="hidden" class="category-data" 
                                       data-id="<?php echo $category['id']; ?>"
                                       data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                       data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
                                       data-type="<?php echo $category['type']; ?>"
                                       data-usage="<?php echo $usage_count; ?>">
                               </div>
                           <?php endforeach; ?>
                       </div>
                   <?php else: ?>
                       <div class="empty-state">
                           <div class="empty-icon">
                               <i class="fas fa-tags"></i>
                           </div>
                           <h3>Chưa có thể loại chi tiêu nào</h3>
                           <p>Hãy thêm thể loại chi tiêu để phân loại các khoản chi của bạn.</p>
                           <button id="emptyAddExpenseCategoryBtn" class="btn-primary">
                               <i class="fas fa-plus"></i> Thêm thể loại chi tiêu
                           </button>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
       </div>
   </main>
</div>

<!-- Modal thêm thể loại -->
<div id="addCategoryModal" class="modal">
   <div class="modal-content">
       <div class="modal-header">
           <h2>Thêm thể loại mới</h2>
           <button class="close-modal">&times;</button>
       </div>
       <div class="modal-body">
           <form id="addCategoryForm" method="POST" action="">
               <input type="hidden" name="action" value="add">
               <div class="form-group">
                   <label for="modal-name">Tên thể loại:</label>
                   <input type="text" id="modal-name" name="name" required>
               </div>
               
               <div class="form-group">
                   <label for="modal-description">Mô tả:</label>
                   <textarea id="modal-description" name="description" rows="3"></textarea>
               </div>
               
               <div class="form-group">
                   <label for="modal-type">Loại giao dịch:</label>
                   <select id="modal-type" name="type" required>
                       <option value="income">Thu nhập</option>
                       <option value="expense">Chi tiêu</option>
                   </select>
               </div>
               
               <div class="form-actions">
                   <button type="submit" class="btn-primary">Lưu thể loại</button>
                   <button type="button" class="btn-secondary cancel-modal">Hủy</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- Modal chỉnh sửa thể loại -->
<div id="editCategoryModal" class="modal">
   <div class="modal-content">
       <div class="modal-header">
           <h2>Chỉnh sửa thể loại</h2>
           <button class="close-modal">&times;</button>
       </div>
       <div class="modal-body">
           <form id="editCategoryForm" method="POST" action="">
               <input type="hidden" name="action" value="update">
               <input type="hidden" name="category_id" id="edit-category-id">
               <div class="form-group">
                   <label for="edit-name">Tên thể loại:</label>
                   <input type="text" id="edit-name" name="name" required>
               </div>
               
               <div class="form-group">
                   <label for="edit-description">Mô tả:</label>
                   <textarea id="edit-description" name="description" rows="3"></textarea>
               </div>
               
               <div class="form-group">
                   <label for="edit-type">Loại giao dịch:</label>
                   <select id="edit-type" name="type" required>
                       <option value="income">Thu nhập</option>
                       <option value="expense">Chi tiêu</option>
                   </select>
               </div>
               
               <div class="form-group" id="edit-usage-info">
                   <label>Thông tin sử dụng:</label>
                   <div class="category-usage-info">
                       <p id="edit-usage-text">Thể loại này chưa được sử dụng trong bất kỳ giao dịch nào.</p>
                       <p class="form-hint" id="edit-usage-hint" style="display: none;">Lưu ý: Nếu bạn thay đổi tên thể loại, tất cả các giao dịch sử dụng thể loại này cũng sẽ được cập nhật.</p>
                   </div>
               </div>
               
               <div class="form-actions">
                   <button type="submit" class="btn-primary">Cập nhật</button>
                   <button type="button" class="btn-secondary cancel-modal">Hủy</button>
               </div>
           </form>
       </div>
   </div>
</div>

<style>
   .categories-grid {
       display: grid;
       grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
       gap: 1.5rem;
       margin-top: 1.5rem;
   }
   
   .category-card {
       background-color: var(--card-background);
       border-radius: 0.5rem;
       border: 1px solid var(--border-color);
       padding: 1.5rem;
       transition: all 0.3s ease;
       cursor: pointer;
   }
   
   .category-card:hover {
       box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
       transform: translateY(-2px);
   }
   
   .category-header {
       display: flex;
       justify-content: space-between;
       align-items: center;
       margin-bottom: 1rem;
   }
   
   .category-header h3 {
       margin: 0;
       font-size: 1.25rem;
       font-weight: 600;
   }
   
   .category-actions {
       display: flex;
       gap: 0.5rem;
   }
   
   .category-description {
       color: var(--text-secondary);
       margin-bottom: 1.5rem;
       min-height: 3rem;
   }
   
   .category-meta {
       display: flex;
       justify-content: space-between;
       font-size: 0.875rem;
       color: var(--text-secondary);
       border-top: 1px solid var(--border-color);
       padding-top: 1rem;
   }
   
   .category-form {
       max-width: 600px;
   }
   
   .category-usage-info {
       background-color: var(--hover-color);
       border-radius: 0.5rem;
       padding: 1rem;
       margin-top: 0.5rem;
   }
   
   .category-usage-info p {
       margin: 0;
   }
   
   .category-usage-info p + p {
       margin-top: 0.5rem;
   }
   
   .mt-4 {
       margin-top: 2rem;
   }
   
   /* Animations */
   .modal-content {
       animation: modalSlideIn 0.3s ease;
   }
   
   @keyframes modalSlideIn {
       from {
           opacity: 0;
           transform: translateY(-20px);
       }
       to {
           opacity: 1;
           transform: translateY(0);
       }
   }
   
   .category-card {
       animation: fadeIn 0.5s ease;
   }
   
   @keyframes fadeIn {
       from {
           opacity: 0;
           transform: translateY(10px);
       }
       to {
           opacity: 1;
           transform: translateY(0);
       }
   }
   
   /* Responsive improvements */
   @media (max-width: 768px) {
       .categories-grid {
           grid-template-columns: 1fr;
       }
   }
</style>

<?php include 'includes/footer.php'; ?>

