<?php
/**
 * CPT project + taxonomia project-category (independente do ThemeRain Core).
 */

if (defined('RS_PROJECT_CPT_LOADED')) {
    return;
}
define('RS_PROJECT_CPT_LOADED', true);

/**
 * Slug de rewrite — reutiliza opção legada do ThemeRain se existir.
 */
function rs_project_rewrite_slug(): string {
    $legacy = trim((string) get_option('themerain_portfolio_slug', ''));

    return $legacy !== '' ? $legacy : 'project';
}

function rs_register_project_post_type(): void {
    if (post_type_exists('project')) {
        return;
    }

    register_post_type('project', [
        'labels'              => [
            'name'               => 'Projetos',
            'singular_name'      => 'Projeto',
            'add_new'            => 'Adicionar novo',
            'add_new_item'       => 'Adicionar projeto',
            'edit_item'          => 'Editar projeto',
            'new_item'           => 'Novo projeto',
            'view_item'          => 'Ver projeto',
            'search_items'       => 'Buscar projetos',
            'not_found'          => 'Nenhum projeto encontrado',
            'not_found_in_trash' => 'Nenhum projeto na lixeira',
            'all_items'          => 'Todos os projetos',
            'menu_name'          => 'Projetos',
        ],
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-portfolio',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'has_archive'         => false,
        'hierarchical'        => false,
        'rewrite'             => [
            'slug'       => rs_project_rewrite_slug(),
            'with_front' => false,
        ],
        'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
    ]);
}

function rs_register_project_category_taxonomy(): void {
    if (!post_type_exists('project') || taxonomy_exists('project-category')) {
        return;
    }

    register_taxonomy('project-category', 'project', [
        'labels'            => [
            'name'          => 'Categorias de projeto',
            'singular_name' => 'Categoria de projeto',
            'search_items'  => 'Buscar categorias',
            'all_items'     => 'Todas as categorias',
            'edit_item'     => 'Editar categoria',
            'update_item'   => 'Atualizar categoria',
            'add_new_item'  => 'Adicionar categoria',
            'new_item_name' => 'Nova categoria',
            'menu_name'     => 'Categorias',
        ],
        'public'            => false,
        'publicly_queryable'=> false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'hierarchical'      => true,
        'rewrite'           => false,
    ]);
}

// Depois do ThemeRain Core (init@10) — só registra se o CPT ainda não existir.
add_action('init', 'rs_register_project_post_type', 11);
add_action('init', 'rs_register_project_category_taxonomy', 11);
