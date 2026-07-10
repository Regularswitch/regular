<?php
add_action('init', function () {
    register_post_type(
        'und_translate',
        [
            'labels'      => [
                'name'          => 'translate',
                'singular_name' => 'translate',
            ],
            'public'      => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-translation',
            'show_in_menu' => false,
            'show_in_rest' => true,
            'supports' => array('editor', 'title', 'author', 
'thumbnail'),
        ]
    );

    register_post_type(
        'brand',
        [
            'labels'      => [
                'name'          => 'Marcas',
                'singular_name' => 'Marca',
                'add_new_item'  => 'Adicionar nova marca',
                'edit_item'     => 'Editar marca',
            ],
            'public'       => true,
            'has_archive'  => false,
            'menu_icon'    => 'dashicons-awards',
            'show_in_rest' => true,
            'supports'     => array('title', 'thumbnail', 
'page-attributes'),
            'rewrite'      => array('slug' => 'brand'),
        ]
    );

    register_post_type(
        'intro',
        [
            'labels'      => [
                'name'          => 'Intro',
                'singular_name' => 'Intro',
                'add_new_item'  => 'Adicionar intro',
                'edit_item'     => 'Editar intro',
            ],
            'public'       => true,
            'has_archive'  => false,
            'menu_icon'    => 'dashicons-text-page',
            'show_in_rest' => true,
            'supports'     => array('title'),
            'rewrite'      => array('slug' => 'intro'),
        ]
    );

    register_post_type(
        'footer',
        [
            'labels'      => [
                'name'          => 'Footer',
                'singular_name' => 'Footer',
                'add_new_item'  => 'Adicionar footer',
                'edit_item'     => 'Editar footer',
            ],
            'public'       => true,
            'has_archive'  => false,
            'menu_icon'    => 'dashicons-table-row-after',
            'show_in_rest' => true,
            'supports'     => array('title'),
            'rewrite'      => array('slug' => 'footer'),
        ]
    );

    register_post_type(
        'capabilities',
        [
            'labels'      => [
                'name'          => 'Capacidades',
                'singular_name' => 'Capacidades',
                'add_new_item'  => 'Adicionar capacidades',
                'edit_item'     => 'Editar capacidades',
            ],
            'public'       => true,
            'has_archive'  => false,
            'menu_icon'    => 'dashicons-hammer',
            'show_in_rest' => true,
            'supports'     => array('title'),
            'rewrite'      => array('slug' => 'capabilities'),
        ]
    );
});
