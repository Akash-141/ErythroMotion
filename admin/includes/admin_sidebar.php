<aside class="admin-sidebar">
    <h3 class="admin-sidebar-title">Admin Menu</h3>
    <ul class="admin-sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
               Dashboard
            </a>
        </li>
        <li>
            <a href="manage_messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_messages.php' ? 'active' : ''; ?>">
               Manage Messages
            </a>
        </li>
        <li>
            <a href="manage_users.php" class="<?php $user_pages = ['manage_users.php', 'add_user.php', 'edit_user.php']; echo in_array(basename($_SERVER['PHP_SELF']), $user_pages) ? 'active' : ''; ?>">
               Manage Users
            </a>
        </li>
        <li class="has-submenu">
            <a href="#" class="submenu-toggle <?php $blog_pages = ['manage_blog_categories.php', 'edit_blog_category.php', 'manage_blog_posts.php', 'add_blog_post.php', 'edit_blog_post.php']; echo in_array(basename($_SERVER['PHP_SELF']), $blog_pages) ? 'active' : ''; ?>">Blog Management</a>
            <ul class="admin-submenu">
                <li><a href="manage_blog_categories.php" class="<?php $cat_pages = ['manage_blog_categories.php', 'edit_blog_category.php']; echo in_array(basename($_SERVER['PHP_SELF']), $cat_pages) ? 'sub-active' : ''; ?>">Categories</a></li>
                <li><a href="manage_blog_posts.php" class="<?php $post_pages = ['manage_blog_posts.php', 'add_blog_post.php', 'edit_blog_post.php']; echo in_array(basename($_SERVER['PHP_SELF']), $post_pages) ? 'sub-active' : ''; ?>">Posts</a></li>
            </ul>
        </li>
        <li class="has-submenu">
            <a href="#" class="submenu-toggle <?php $blood_pages = ['manage_donors.php', 'manage_blood_requests.php']; echo in_array(basename($_SERVER['PHP_SELF']), $blood_pages) ? 'active' : ''; ?>">Blood Management</a>
            <ul class="admin-submenu">
                <li><a href="manage_donors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_donors.php' ? 'sub-active' : ''; ?>">Manage Donors</a></li>
                <li><a href="manage_blood_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_blood_requests.php' ? 'sub-active' : ''; ?>">Manage Requests</a></li>
            </ul>
        </li>
        <li>
            <a href="manage_equipments.php" class="<?php $equipment_pages = ['manage_equipments.php', 'add_equipment.php', 'edit_equipment.php']; echo in_array(basename($_SERVER['PHP_SELF']), $equipment_pages) ? 'active' : ''; ?>">
               Manage Equipment
            </a>
        </li>
        <li>
            <a href="manage_orders.php" class="<?php $order_pages = ['manage_orders.php', 'order_details.php']; echo in_array(basename($_SERVER['PHP_SELF']), $order_pages) ? 'active' : ''; ?>">
               Manage Orders
            </a>
        </li>
        <li>
            <a href="manage_exercises.php" class="<?php $exercise_admin_pages = ['manage_exercises.php', 'add_exercise.php', 'edit_exercise.php']; echo in_array(basename($_SERVER['PHP_SELF']), $exercise_admin_pages) ? 'active' : ''; ?>">
               Manage Exercises
            </a>
        </li>
    </ul>
</aside>
<script>
// NOTE: For best practice, this script should be moved to a global admin JavaScript file.
document.addEventListener('DOMContentLoaded', function() {
    var submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            this.parentElement.classList.toggle('open');
        });

        // Keep submenu open if a child link is active
        if (toggle.classList.contains('active')) {
             var submenu = toggle.nextElementSibling;
             if(submenu) {
                toggle.parentElement.classList.add('open');
             }
        }
    });
});
</script>
