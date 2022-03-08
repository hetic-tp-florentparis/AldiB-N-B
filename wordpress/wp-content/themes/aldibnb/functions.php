<?php 
function wphetic_theme_support() {
    add_theme_support('title-tag');
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'menus' );
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

function createPost()
{
    if(/* current_user_can('event_rights')
    &&  */wp_verify_nonce($_POST['upload_post_nonce'], 'upload_post')){
        $post_args =array(
            'post_content' => $_POST["message_post"],
            'post_title' => $_POST["post_title"],
            'post_type'=> 'post',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'comment_status'=> 'closed',
            /*TODO rajouter la catégorie
            'taxt_input' => [
                'style' => [$_POST['']]
            ],
            */
            'meta_input'=>array(
                'post_price' => $_POST["post_price"]
            )
        );
        //Insérer un post en base de données
        $post_id = wp_insert_post($post_args);
        //Traitement d'upload d'image - Si tout a marché -> rattache l'image au post
        $attachment_id = media_handle_upload('post_image', $post_id);
        var_dump($attachment_id);
        if(is_wp_error($attachment_id)){
            wp_redirect($_POST['_wp_http_referer']. '.status=error'); //redirect objet d'erreur
        }else{
            set_post_thumbnail($post_id, $attachment_id);
            wp_redirect(home_url('?p='. $post_id));
        }
    }else{
        wp_redirect($_POST['_wp_http_referer'].'?status=no_once');
    }
};
add_action('admin_post_upload_post', 'createPost');

function wphetic_register_style_taxonomy(){
    
    $labels = [
        'name' => 'Styles',
        'singular_name' => 'Style',
        'search_items' => 'Rechercher style',
        'all_items' => 'Tous les styles'
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true
    ];

    register_taxonomy('style', ['post'], $args);

}

function wphetic_register_event_cpt(){
    
    $labels = [
        'name' => 'Evènements',
        'singular_name' => 'Evènements',
        'search_items' => 'Rechercher évènements',
        'all_items' => 'Tous les évènements'
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicon-tickets',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'has_archive' => true,
        'taxonomies' => ['style'],
        'capabilities' => array(
            'edit_post' => 'event_rights',
            'read_post' => 'event_rights',
            'delte_post' => 'event_rights',
            //On définit quel droit permet de faire tel action
        ),
    ];

    register_post_type('event', $args);

}

add_action('after_setup_theme','wphetic_theme_support');

/*Ajout de bootstrap*/
add_action('wp_enqueue_scripts','wphetic_bootstrap');

/*Ajout de la taxonomie*/
add_action('init','wphetic_register_style_taxonomy');

/*Ajout d'un terme à la taxonomie 'style'*/
add_action('after_switch_theme', function() {
    wp_insert_term('Dubstep', 'style'); //Insertion du terme 'Dubstep' dans la taxonomie 'style'
    flush_rewrite_rules(); //Empêche les bugs de réécriture d'URL
});

/*Ajout de post custom*/
add_action('init','wphetic_register_event_cpt');

/*Ajout des droits 'manage_events' à l'utilisateur 'administrator'*/
add_action('after_switch_theme', function(){
    $admin = get_role('administrator'); //Récupération du rôle 'administrator
    $admin->add_cap('event_rights'); //Ajout des droits au rôle
});

/*Ajout du rôle Event Manager*/
add_action('after_switch_theme', function() {
    add_role('event_manager', 'Event Manager', [  //Création du rôle
        'read' => true, //Ajout du droit pour lire
        'event_rights' => true //Ajout du droit pour gérer les events
    ]);
});

/*Ajout du rôle Utilisateur*/
add_action('after_switch_theme', function() {
    add_role('utilisateur', 'Utilisateur', [  //Création du rôle
        'event_rights' => true, //Ajout du droit pour gérer les events
        'delete_posts' => true,
        'edit_posts' => true,
        'publish_posts' => true
        //C'est ici qu'on définiera les droits de nos utilisateurs
    ]);
});

/*Ajout du rôle Manager*/
add_action('after_switch_theme', function() {
    add_role('manager', 'Manager', [  //Création du rôle
        'read' => true, //Ajout du droit pour lire
        'event_rights' => true, //Ajout du droit pour gérer les events
        'delete_posts' => true,
        'edit_posts' => true,
        'publish_posts' => true,
        'delete_others_posts' => true,
        'delete_published_posts' => true,
        'delete_private_posts' => true,
        'edit_others_posys' => true,
        'edit_private_posts' => true,
        'edit_published_posts' => true,
        'read_privates_posts' => true
        //C'est ici qu'on définiera les droits de nos utilisateurs
    ]);
});

/*Nettoyer les droits donnés aux utilisateurs pour ne pas entacher les autres thèmes*/
add_action('switch_theme', function() {
    $admin = get_role('administrator'); //Récupération du rôle 'administrator
    $admin->remove_cap('event_rights'); //Suppression des droits de l'utilisateur
    remove_role('utilisateur'); //Suppression du droit 
});


require_once('options/BannerMessage.php');

BannerMessage::register();