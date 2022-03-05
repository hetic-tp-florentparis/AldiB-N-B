<?php 
function wphetic_theme_support() {
    add_theme_support('title-tag');
    add_theme_support( 'post-thumbnails' );
}

function wphetic_bootstrap() 
{
    /* Bootstrap */
    wp_enqueue_style('bootstrap_css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap_js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', 
    [], false, true);

    /* Perso Style */
    wp_enqueue_style('style_perso', get_template_directory_uri() . '/assets/style/main.css', array(), '1.0', 'all');
    /* Perso Script Components */
    wp_enqueue_script('script_navbar',
    get_template_directory_uri() . '/assets/script/components/navbar.js', 
    [], false, true);
    /* Perso Style Pages */
    wp_enqueue_script('script_homepage',
    get_template_directory_uri() . '/assets/script/pages/homepage.js', 
    [], false, true);
}

add_filter('nav_menu_css_class', function ($classes) {
    $classes[] = "nav-item";
    return $classes;
});

add_filter('nav_menu_link_attributes', function ($attr) {
    $attr['class'] = 'nav-link';
    return $attr;
});

function wpheticPaginate()
{
    $pages = paginate_links(['type' => 'array']);
    if (!$pages) {
        return null;
    }   

    ob_start();
    echo '<nav aria-label="Page navigation example">';
    echo '<ul class="pagination">';

    foreach ($pages as $page) {
        $active = strpos($page, 'current');
        $liClass = $active ? 'page-item active' : 'page-item';
        $page = str_replace('page-numbers', 'page-link', $page);

        echo sprintf('<li class="%s">%s</li>', $liClass, $page);
    }
    echo '</ul></nav>';

    return ob_get_clean();
}

function createUser() 
{
    if(isset($_POST["admin"])){
        $admin = "administrateur";
    }else{
        $admin = "";
    }

    wp_insert_user( array(
        'user_pass' => $_POST["pwd"],
        'user_login' => $_POST["username"],
        'user_email' => $_POST["log"],
        'role' => $admin
    ));
};

add_action('after_setup_theme','wphetic_theme_support');
add_action('wp_enqueue_scripts','wphetic_bootstrap');