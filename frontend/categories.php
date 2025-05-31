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
       $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       
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
       $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
       
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
    <!-- Enhanced Magical Header -->
    <header class="magical-header" style="background:var(--card-background);border-bottom:1px solid var(--border-color);padding:2rem 0;position:relative;overflow:hidden;">
        <!-- Animated Background Pattern -->
        <div class="header-pattern" style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.03;background:radial-gradient(circle at 30% 40%, var(--accent-color) 2px, transparent 2px), radial-gradient(circle at 70% 60%, var(--primary-color) 1px, transparent 1px);background-size:45px 45px;animation:patternFloat 18s ease-in-out infinite;"></div>
        
        <!-- Floating Particles -->
        <div class="floating-particles" style="position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;">
            <div class="particle" style="position:absolute;width:6px;height:6px;background:var(--accent-color);border-radius:50%;opacity:0.4;top:15%;left:15%;animation:floatCategory1 9s ease-in-out infinite;"></div>
            <div class="particle" style="position:absolute;width:4px;height:4px;background:var(--positive-color);border-radius:50%;opacity:0.5;top:70%;left:85%;animation:floatCategory2 7s ease-in-out infinite 1.5s;"></div>
            <div class="particle" style="position:absolute;width:5px;height:5px;background:var(--primary-color);border-radius:50%;opacity:0.3;top:50%;left:50%;animation:floatCategory3 11s ease-in-out infinite 0.5s;"></div>
        </div>

        <div class="header-content" style="max-width:1400px;margin:0 auto;padding:0 2rem;display:flex;justify-content:space-between;align-items:center;position:relative;z-index:2;">
            <div class="header-left" style="animation:slideInLeft 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
                <h1 class="magical-title" style="margin:0 0 8px;color:var(--primary-color);font-size:2rem;font-weight:700;position:relative;display:inline-block;">
                    🏷️ Quản lý thể loại
                    <span class="title-glow" style="position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(45deg,transparent,rgba(102,126,234,0.3),transparent);animation:titleGlow 3s ease-in-out infinite;"></span>
                </h1>
                <p class="magical-subtitle" style="margin:0;color:var(--secondary-color);font-size:1rem;animation:fadeInUp 1s ease-out 0.3s both;">Tạo và quản lý các thể loại thu chi của bạn</p>
                <div class="breadcrumb" style="margin-top:8px;display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--secondary-color);animation:fadeInUp 1s ease-out 0.6s both;">
                    <i class="fas fa-home" style="animation:iconSpin 4s ease-in-out infinite;"></i>
                    <span>Trang chủ</span>
                    <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
                    <span style="color:var(--accent-color);">Thể loại</span>
                </div>
            </div>
            <div class="header-right" style="display:flex;gap:12px;align-items:center;animation:slideInRight 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);">
                <a href="index.php" class="magical-button back-btn" style="background:var(--hover-color);color:var(--primary-color);padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:500;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--border-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.background='var(--hover-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <i class="fas fa-arrow-left" style="animation:iconBounce 2s ease-in-out infinite;"></i> 
                    Quay lại
                    <span class="button-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                </a>
                <button id="addCategoryBtn" class="magical-button primary-btn" style="background:var(--accent-color);color:white;border:none;border-radius:12px;padding:12px 18px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.4)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <i class="fas fa-plus" style="animation:iconSpin 3s ease-in-out infinite;"></i>
                    Thêm thể loại
                    <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                </button>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding:2rem;max-width:1400px;margin:0 auto;">
        <?php echo displayFlashMessages(); ?>
        
        <!-- Enhanced Magical Tab Navigation -->
        <div class="magical-content-tabs" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;animation:tabsSlideUp 0.8s ease-out 0.2s both;">
            <div class="tabs-glow" style="position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(45deg,rgba(102,126,234,0.05),rgba(102,126,234,0.1),rgba(102,126,234,0.05));opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
            
            <nav class="magical-tab-nav" style="background:linear-gradient(135deg,var(--hover-color),rgba(248,250,252,0.8));padding:8px;display:flex;gap:6px;position:relative;z-index:2;">
                <button class="magical-tab-button active" data-tab="income-categories" style="flex:1;padding:16px 24px;border:none;background:var(--card-background);color:var(--primary-color);border-radius:12px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 16px rgba(0,0,0,0.15);position:relative;overflow:hidden;" onmouseover="this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(34,197,94,0.25)'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.15)'">
                    <span class="tab-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:tabShine 3s ease-in-out infinite;"></span>
                    <i class="fas fa-arrow-up" style="margin-right:8px;color:var(--positive-color);animation:iconFloat 3s ease-in-out infinite;"></i> 
                    <span style="position:relative;z-index:2;">💰 Thể loại thu nhập</span>
                </button>
                <button class="magical-tab-button" data-tab="expense-categories" style="flex:1;padding:16px 24px;border:none;background:transparent;color:var(--secondary-color);border-radius:12px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);position:relative;overflow:hidden;" onmouseover="this.style.background='rgba(255,255,255,0.7)';this.style.color='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.background='transparent';this.style.color='var(--secondary-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                    <span class="tab-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(239,68,68,0.2);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                    <i class="fas fa-arrow-down" style="margin-right:8px;color:var(--negative-color);animation:iconFloat 3s ease-in-out infinite 0.5s;"></i> 
                    <span style="position:relative;z-index:2;">💸 Thể loại chi tiêu</span>
                </button>
            </nav>
            
            <div class="tab-content" style="padding:28px;">
                <!-- Tab Thể loại thu nhập -->
                <div id="income-categories" class="tab-pane active" style="animation:fadeInUp 0.6s ease-out;">
                    <div class="magical-section-header" style="margin-bottom:24px;text-align:center;">
                        <div class="section-icon" style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(34,197,94,0.1),rgba(34,197,94,0.2));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;animation:iconFloat 3s ease-in-out infinite;">
                            <i class="fas fa-coins" style="color:var(--positive-color);font-size:1.5rem;"></i>
                        </div>
                        <h2 style="margin:0 0 8px;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Thể loại thu nhập</h2>
                        <p style="margin:0;color:var(--secondary-color);font-size:1rem;">Quản lý các danh mục thu nhập của bạn</p>
                    </div>
                    
                    <?php if (count($income_categories) > 0): ?>
                        <div class="magical-categories-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;">
                            <?php foreach ($income_categories as $index => $category): ?>
                                <?php $usage_count = getCategoryUsageCount($conn, $user_id, $category['name']); ?>
                                <div class="magical-category-card income-card" data-id="<?php echo $category['id']; ?>" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:24px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out <?php echo $index * 0.1; ?>s both;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(34,197,94,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
                                    <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(34,197,94,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
                                    
                                    <div class="category-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;position:relative;z-index:2;">
                                        <div style="flex:1;">
                                            <h3 style="margin:0 0 4px;color:var(--primary-color);font-size:1.2rem;font-weight:700;display:flex;align-items:center;gap:8px;">
                                                <div style="width:10px;height:10px;border-radius:50%;background:var(--positive-color);animation:pulse 2s ease-in-out infinite;"></div>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </h3>
                                            <div style="font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Tạo ngày: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?></div>
                                        </div>
                                        <div class="magical-category-actions" style="display:flex;gap:8px;">
                                            <button class="magical-btn-icon edit-category-btn" title="Chỉnh sửa" data-id="<?php echo $category['id']; ?>" style="background:var(--accent-color);color:white;border:none;border-radius:8px;padding:8px;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);font-size:0.8rem;cursor:pointer;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 4px 15px rgba(102,126,234,0.3)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                                <i class="fas fa-edit"></i>
                                                <span class="btn-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                                            </button>
                                            <a href="categories.php?delete=<?php echo $category['id']; ?>" class="magical-btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa thể loại này?');" style="background:#ef4444;color:white;border:none;border-radius:8px;padding:8px;text-decoration:none;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);font-size:0.8rem;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;" onmouseover="this.style.background='#dc2626';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 4px 15px rgba(239,68,68,0.3)'" onmouseout="this.style.background='#ef4444';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                                <i class="fas fa-trash"></i>
                                                <span class="btn-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="category-description" style="color:var(--secondary-color);font-size:0.95rem;line-height:1.5;margin-bottom:16px;position:relative;z-index:2;min-height:48px;display:flex;align-items:center;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <i class="fas fa-quote-left" style="color:var(--accent-color);font-size:0.8rem;opacity:0.6;"></i>
                                            <span><?php echo htmlspecialchars($category['description'] ?: 'Không có mô tả cụ thể'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="category-meta" style="display:flex;justify-content:space-between;align-items:center;position:relative;z-index:2;">
                                        <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:var(--secondary-color);">
                                            <i class="fas fa-tag" style="color:var(--accent-color);font-size:0.7rem;"></i>
                                            <span>Thu nhập</span>
                                        </div>
                                        <?php if ($usage_count > 0): ?>
                                            <div class="usage-badge" style="background:linear-gradient(135deg,var(--positive-color),rgba(34,197,94,0.8));color:white;padding:4px 12px;border-radius:20px;font-weight:600;font-size:0.8rem;display:flex;align-items:center;gap:6px;">
                                                <i class="fas fa-chart-line" style="font-size:0.7rem;"></i>
                                                Đã dùng: <?php echo $usage_count; ?> lần
                                            </div>
                                        <?php else: ?>
                                            <div class="unused-badge" style="background:var(--hover-color);color:var(--secondary-color);padding:4px 12px;border-radius:20px;font-weight:500;font-size:0.8rem;">
                                                Chưa sử dụng
                                            </div>
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
                        <div class="magical-empty-state" style="text-align:center;padding:60px 40px;background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;position:relative;overflow:hidden;">
                            <div class="empty-glow" style="position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle at center,rgba(34,197,94,0.05) 0%,transparent 70%);animation:emptyGlowPulse 4s ease-in-out infinite;"></div>
                            <div style="position:relative;z-index:2;">
                                <div class="empty-icon" style="font-size:4rem;color:var(--secondary-color);margin-bottom:20px;opacity:0.6;animation:iconFloat 3s ease-in-out infinite;">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <h3 style="margin:0 0 12px;color:var(--primary-color);font-size:1.3rem;font-weight:700;">Chưa có thể loại thu nhập nào</h3>
                                <p style="margin:0 0 28px;color:var(--secondary-color);font-size:1rem;max-width:400px;margin-left:auto;margin-right:auto;line-height:1.5;">Hãy thêm thể loại thu nhập để phân loại các khoản thu của bạn một cách chi tiết và dễ quản lý.</p>
                                <button id="emptyAddIncomeCategoryBtn" class="magical-button primary" style="background:var(--accent-color);color:white;border:none;border-radius:12px;padding:12px 24px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:inline-flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.4)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                    <i class="fas fa-plus" style="animation:iconBounce 2s ease-in-out infinite;"></i>
                                    Thêm thể loại thu nhập
                                    <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab Thể loại chi tiêu -->
                <div id="expense-categories" class="tab-pane">
                    <div class="magical-section-header" style="margin-bottom:24px;text-align:center;">
                        <div class="section-icon" style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(239,68,68,0.1),rgba(239,68,68,0.2));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;animation:iconFloat 3s ease-in-out infinite;">
                            <i class="fas fa-shopping-cart" style="color:var(--negative-color);font-size:1.5rem;"></i>
                        </div>
                        <h2 style="margin:0 0 8px;color:var(--primary-color);font-size:1.5rem;font-weight:700;">Thể loại chi tiêu</h2>
                        <p style="margin:0;color:var(--secondary-color);font-size:1rem;">Quản lý các danh mục chi tiêu của bạn</p>
                    </div>
                    
                    <?php if (count($expense_categories) > 0): ?>
                        <div class="magical-categories-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;">
                            <?php foreach ($expense_categories as $index => $category): ?>
                                <?php $usage_count = getCategoryUsageCount($conn, $user_id, $category['name']); ?>
                                <div class="magical-category-card expense-card" data-id="<?php echo $category['id']; ?>" style="background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;padding:24px;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);box-shadow:0 4px 20px rgba(0,0,0,0.08);position:relative;overflow:hidden;animation:cardSlideIn 0.8s ease-out <?php echo $index * 0.1; ?>s both;" onmouseover="this.style.transform='translateY(-8px) scale(1.02)';this.style.boxShadow='0 12px 40px rgba(239,68,68,0.15)';this.querySelector('.card-glow').style.opacity='1'" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.08)';this.querySelector('.card-glow').style.opacity='0'">
                                    <div class="card-glow" style="position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(239,68,68,0.1) 0%,transparent 70%);opacity:0;transition:opacity 0.4s ease;pointer-events:none;"></div>
                                    
                                    <div class="category-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;position:relative;z-index:2;">
                                        <div style="flex:1;">
                                            <h3 style="margin:0 0 4px;color:var(--primary-color);font-size:1.2rem;font-weight:700;display:flex;align-items:center;gap:8px;">
                                                <div style="width:10px;height:10px;border-radius:50%;background:var(--negative-color);animation:pulse 2s ease-in-out infinite;"></div>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </h3>
                                            <div style="font-size:0.8rem;color:var(--secondary-color);opacity:0.8;">Tạo ngày: <?php echo date('d/m/Y', strtotime($category['created_at'])); ?></div>
                                        </div>
                                        <div class="magical-category-actions" style="display:flex;gap:8px;">
                                            <button class="magical-btn-icon edit-category-btn" title="Chỉnh sửa" data-id="<?php echo $category['id']; ?>" style="background:var(--accent-color);color:white;border:none;border-radius:8px;padding:8px;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);font-size:0.8rem;cursor:pointer;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 4px 15px rgba(102,126,234,0.3)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                                <i class="fas fa-edit"></i>
                                                <span class="btn-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                                            </button>
                                            <a href="categories.php?delete=<?php echo $category['id']; ?>" class="magical-btn-icon delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa thể loại này?');" style="background:#ef4444;color:white;border:none;border-radius:8px;padding:8px;text-decoration:none;transition:all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);font-size:0.8rem;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;" onmouseover="this.style.background='#dc2626';this.style.transform='translateY(-2px) scale(1.05)';this.style.boxShadow='0 4px 15px rgba(239,68,68,0.3)'" onmouseout="this.style.background='#ef4444';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                                <i class="fas fa-trash"></i>
                                                <span class="btn-ripple" style="position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.3);transform:translate(-50%,-50%);transition:all 0.6s ease;"></span>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="category-description" style="color:var(--secondary-color);font-size:0.95rem;line-height:1.5;margin-bottom:16px;position:relative;z-index:2;min-height:48px;display:flex;align-items:center;">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <i class="fas fa-quote-left" style="color:var(--accent-color);font-size:0.8rem;opacity:0.6;"></i>
                                            <span><?php echo htmlspecialchars($category['description'] ?: 'Không có mô tả cụ thể'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="category-meta" style="display:flex;justify-content:space-between;align-items:center;position:relative;z-index:2;">
                                        <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:var(--secondary-color);">
                                            <i class="fas fa-tag" style="color:var(--accent-color);font-size:0.7rem;"></i>
                                            <span>Chi tiêu</span>
                                        </div>
                                        <?php if ($usage_count > 0): ?>
                                            <div class="usage-badge" style="background:linear-gradient(135deg,var(--negative-color),rgba(239,68,68,0.8));color:white;padding:4px 12px;border-radius:20px;font-weight:600;font-size:0.8rem;display:flex;align-items:center;gap:6px;">
                                                <i class="fas fa-chart-line" style="font-size:0.7rem;"></i>
                                                Đã dùng: <?php echo $usage_count; ?> lần
                                            </div>
                                        <?php else: ?>
                                            <div class="unused-badge" style="background:var(--hover-color);color:var(--secondary-color);padding:4px 12px;border-radius:20px;font-weight:500;font-size:0.8rem;">
                                                Chưa sử dụng
                                            </div>
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
                        <div class="magical-empty-state" style="text-align:center;padding:60px 40px;background:var(--card-background);border:1px solid var(--border-color);border-radius:16px;position:relative;overflow:hidden;">
                            <div class="empty-glow" style="position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle at center,rgba(239,68,68,0.05) 0%,transparent 70%);animation:emptyGlowPulse 4s ease-in-out infinite;"></div>
                            <div style="position:relative;z-index:2;">
                                <div class="empty-icon" style="font-size:4rem;color:var(--secondary-color);margin-bottom:20px;opacity:0.6;animation:iconFloat 3s ease-in-out infinite;">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h3 style="margin:0 0 12px;color:var(--primary-color);font-size:1.3rem;font-weight:700;">Chưa có thể loại chi tiêu nào</h3>
                                <p style="margin:0 0 28px;color:var(--secondary-color);font-size:1rem;max-width:400px;margin-left:auto;margin-right:auto;line-height:1.5;">Hãy thêm thể loại chi tiêu để phân loại các khoản chi của bạn một cách chi tiết và dễ quản lý.</p>
                                <button id="emptyAddExpenseCategoryBtn" class="magical-button primary" style="background:var(--accent-color);color:white;border:none;border-radius:12px;padding:12px 24px;font-weight:600;transition:all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);display:inline-flex;align-items:center;gap:8px;position:relative;overflow:hidden;" onmouseover="this.style.background='var(--primary-color)';this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 8px 25px rgba(102,126,234,0.4)'" onmouseout="this.style.background='var(--accent-color)';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='none'">
                                    <i class="fas fa-plus" style="animation:iconBounce 2s ease-in-out infinite;"></i>
                                    Thêm thể loại chi tiêu
                                    <span class="button-shine" style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:buttonShine 3s ease-in-out infinite;"></span>
                                </button>
                            </div>
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
       align-items: flex-start;
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

   /* Enhanced magical tab styles */
   .magical-tab-nav {
       position: relative;
   }

   .magical-tab-button {
       transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
       position: relative;
       overflow: hidden;
   }

   .magical-tab-button:not(.active):hover {
       background: rgba(255,255,255,0.7) !important;
       color: var(--primary-color) !important;
       transform: translateY(-2px) scale(1.02) !important;
       box-shadow: 0 4px 16px rgba(0,0,0,0.1) !important;
   }

   .tab-pane {
       transition: opacity 0.4s ease, transform 0.4s ease;
       opacity: 0;
       transform: translateY(15px);
   }

   .tab-pane.active {
       opacity: 1;
       transform: translateY(0);
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

       .magical-tab-nav {
           padding: 6px;
           gap: 4px;
       }

       .magical-tab-button {
           padding: 14px 16px !important;
           font-size: 0.9rem;
       }

       .magical-tab-button span {
           display: block;
           text-align: center;
       }
   }

   @media (max-width: 480px) {
       .magical-tab-button {
           padding: 12px 8px !important;
           font-size: 0.8rem;
       }

       .magical-tab-button i {
           margin-right: 4px;
       }
   }
</style>

<script>
// Khởi tạo trạng thái tab từ đầu
document.addEventListener('DOMContentLoaded', function() {
    // Đảm bảo tab đầu tiên được active đúng cách
    const firstTab = document.querySelector('.magical-tab-button[data-tab="income-categories"]');
    const firstPane = document.getElementById('income-categories');
    
    if (firstTab && firstPane) {
        // Đảm bảo trạng thái active đúng
        firstTab.classList.add('active');
        firstTab.style.background = 'var(--card-background)';
        firstTab.style.color = 'var(--primary-color)';
        firstTab.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
        firstTab.style.transform = 'translateY(-2px) scale(1.02)';
        
        firstPane.classList.add('active');
        firstPane.style.display = 'block';
        firstPane.style.opacity = '1';
        firstPane.style.transform = 'translateY(0)';
    }

    // Xử lý chuyển đổi tab
    const tabButtons = document.querySelectorAll('.magical-tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            console.log('Switching to tab:', targetTab);
            
            // Xóa active class từ tất cả tabs
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'transparent';
                btn.style.color = 'var(--secondary-color)';
                btn.style.boxShadow = 'none';
                btn.style.transform = 'translateY(0) scale(1)';
            });
            
            // Ẩn tất cả tab panes
            tabPanes.forEach(pane => {
                pane.classList.remove('active');
                pane.style.opacity = '0';
                pane.style.transform = 'translateY(15px)';
                setTimeout(() => {
                    if (!pane.classList.contains('active')) {
                        pane.style.display = 'none';
                    }
                }, 200);
            });
            
            // Active tab được click
            this.classList.add('active');
            this.style.background = 'var(--card-background)';
            this.style.color = 'var(--primary-color)';
            this.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
            this.style.transform = 'translateY(-2px) scale(1.02)';
            
            // Hiển thị tab pane tương ứng
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                setTimeout(() => {
                    targetPane.style.display = 'block';
                    targetPane.classList.add('active');
                    setTimeout(() => {
                        targetPane.style.opacity = '1';
                        targetPane.style.transform = 'translateY(0)';
                    }, 50);
                }, 200);
            }
            
            console.log('Tab switched successfully to:', targetTab);
        });
    });

    // Tối ưu cho mobile - thu gọn text nếu màn hình quá nhỏ
    function optimizeTabsForMobile() {
        const tabButtons = document.querySelectorAll('.magical-tab-button span');
        if (window.innerWidth <= 480) {
            tabButtons.forEach(span => {
                if (span.textContent.includes('💰 Thể loại thu nhập')) {
                    span.innerHTML = '<i class="fas fa-arrow-up"></i> Thu nhập';
                } else if (span.textContent.includes('💸 Thể loại chi tiêu')) {
                    span.innerHTML = '<i class="fas fa-arrow-down"></i> Chi tiêu';
                }
            });
        }
    }

    optimizeTabsForMobile();
    window.addEventListener('resize', optimizeTabsForMobile);

    // Xử lý modal thêm thể loại
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    const emptyAddIncomeCategoryBtn = document.getElementById('emptyAddIncomeCategoryBtn');
    const emptyAddExpenseCategoryBtn = document.getElementById('emptyAddExpenseCategoryBtn');
    const addCategoryModal = document.getElementById('addCategoryModal');
    const editCategoryModal = document.getElementById('editCategoryModal');
    
    // Mở modal thêm thể loại
    function openAddModal(defaultType = 'income') {
        const modalType = document.getElementById('modal-type');
        if (modalType) {
            modalType.value = defaultType;
        }
        addCategoryModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', () => {
            // Xác định tab hiện tại để set default type
            const activeTab = document.querySelector('.magical-tab-button.active');
            const defaultType = activeTab && activeTab.getAttribute('data-tab') === 'expense-categories' ? 'expense' : 'income';
            openAddModal(defaultType);
        });
    }
    
    if (emptyAddIncomeCategoryBtn) {
        emptyAddIncomeCategoryBtn.addEventListener('click', () => openAddModal('income'));
    }
    
    if (emptyAddExpenseCategoryBtn) {
        emptyAddExpenseCategoryBtn.addEventListener('click', () => openAddModal('expense'));
    }
    
    // Đóng modal
    function closeModal(modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Event listeners cho đóng modal
    document.querySelectorAll('.close-modal, .cancel-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Đóng modal khi click outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
    
    // Xử lý nút edit
    document.querySelectorAll('.edit-category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');
            const categoryData = document.querySelector(`.category-data[data-id="${categoryId}"]`);
            
            if (categoryData) {
                document.getElementById('edit-category-id').value = categoryId;
                document.getElementById('edit-name').value = categoryData.getAttribute('data-name');
                document.getElementById('edit-description').value = categoryData.getAttribute('data-description');
                document.getElementById('edit-type').value = categoryData.getAttribute('data-type');
                
                const usage = parseInt(categoryData.getAttribute('data-usage'));
                const usageText = document.getElementById('edit-usage-text');
                const usageHint = document.getElementById('edit-usage-hint');
                
                if (usage > 0) {
                    usageText.textContent = `Thể loại này đã được sử dụng trong ${usage} giao dịch.`;
                    usageHint.style.display = 'block';
                } else {
                    usageText.textContent = 'Thể loại này chưa được sử dụng trong bất kỳ giao dịch nào.';
                    usageHint.style.display = 'none';
                }
                
                editCategoryModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    console.log('✅ Categories page JavaScript initialized successfully!');
});
</script>

<?php include 'includes/footer.php'; ?>
