<?php
add_action('init', function () {
    register_post_type(
        'und_translate',
        [
            'labels'       => [
                'name'          => 'translate',
                'singular_name' => 'translate',
            ],
            'public'       => true,
            'has_archive'  => true,
            'menu_icon'    => 'dashicons-translation',
            'show_in_menu' => false,
            'show_in_rest' => true,
            'supports'     => ['editor', 'title', 'author', 'thumbnail'],
        ]
    );

    register_post_type(
        'intro',
        [
            'labels'        => [
                'name'          => 'Intro',
                'singular_name' => 'Intro',
                'add_new_item'  => 'Adicionar intro',
                'edit_item'     => 'Editar intro',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 27,
            'menu_icon'     => 'dashicons-text-page',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'intro'],
        ]
    );

    register_post_type(
        'home-visual',
        [
            'labels'        => [
                'name'          => 'Visual da home',
                'singular_name' => 'Visual da home',
                'add_new_item'  => 'Adicionar visual',
                'edit_item'     => 'Editar visual da home',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 28,
            'menu_icon'     => 'dashicons-art',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'home-visual'],
        ]
    );

    register_post_type(
        'page-heroes',
        [
            'labels'        => [
                'name'          => 'Heroes',
                'singular_name' => 'Heroes',
                'add_new_item'  => 'Editar heroes',
                'edit_item'     => 'Editar heroes',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 29.5,
            'menu_icon'     => 'dashicons-format-image',
            'show_in_menu'  => false,
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'page-heroes'],
        ]
    );

    register_post_type(
        'site-ui',
        [
            'labels'        => [
                'name'          => 'Interface do site',
                'singular_name' => 'Interface do site',
                'add_new_item'  => 'Adicionar interface',
                'edit_item'     => 'Editar interface',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 29,
            'menu_icon'     => 'dashicons-admin-generic',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'site-ui'],
        ]
    );

    register_post_type(
        'about',
        [
            'labels'        => [
                'name'          => 'Sobre Nós',
                'singular_name' => 'Sobre Nós',
                'add_new_item'  => 'Adicionar sobre',
                'edit_item'     => 'Editar sobre',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 30,
            'menu_icon'     => 'dashicons-groups',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'about'],
        ]
    );

    register_post_type(
        'projects-page',
        [
            'labels'        => [
                'name'          => 'Página de projetos',
                'singular_name' => 'Página de projetos',
                'add_new_item'  => 'Adicionar página de projetos',
                'edit_item'     => 'Editar página de projetos',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 31,
            'menu_icon'     => 'dashicons-portfolio',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'projects-page'],
        ]
    );

    register_post_type(
        'capabilities',
        [
            'labels'        => [
                'name'          => 'Capacidades',
                'singular_name' => 'Capacidades',
                'add_new_item'  => 'Adicionar capacidades',
                'edit_item'     => 'Editar capacidades',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 32,
            'menu_icon'     => 'dashicons-hammer',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'capabilities'],
        ]
    );

    register_post_type(
        'education',
        [
            'labels'        => [
                'name'          => 'Educação',
                'singular_name' => 'Educação',
                'add_new_item'  => 'Adicionar educação',
                'edit_item'     => 'Editar educação',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 33,
            'menu_icon'     => 'dashicons-welcome-learn-more',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'education'],
        ]
    );

    register_post_type(
        'brand',
        [
            'labels'        => [
                'name'          => 'Marcas',
                'singular_name' => 'Marca',
                'add_new_item'  => 'Adicionar nova marca',
                'edit_item'     => 'Editar marca',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 34,
            'menu_icon'     => 'dashicons-awards',
            'show_in_rest'  => true,
            'supports'      => ['title', 'thumbnail', 'page-attributes'],
            'rewrite'       => ['slug' => 'brand'],
        ]
    );

    register_post_type(
        'contact',
        [
            'labels'        => [
                'name'          => 'Contato',
                'singular_name' => 'Contato',
                'add_new_item'  => 'Adicionar contato',
                'edit_item'     => 'Editar contato',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 35,
            'menu_icon'     => 'dashicons-email',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'contact'],
        ]
    );

    register_post_type(
        'legal',
        [
            'labels'        => [
                'name'          => 'Privacidade & Cookies',
                'singular_name' => 'Privacidade & Cookies',
                'add_new_item'  => 'Adicionar políticas',
                'edit_item'     => 'Editar políticas',
                'menu_name'     => 'Privacidade & Cookies',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 37,
            'menu_icon'     => 'dashicons-privacy',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'legal'],
        ]
    );

    register_post_type(
        'footer',
        [
            'labels'        => [
                'name'          => 'Footer',
                'singular_name' => 'Footer',
                'add_new_item'  => 'Adicionar footer',
                'edit_item'     => 'Editar footer',
            ],
            'public'        => true,
            'has_archive'   => false,
            'menu_position' => 36,
            'menu_icon'     => 'dashicons-table-row-after',
            'show_in_rest'  => true,
            'supports'      => ['title'],
            'rewrite'       => ['slug' => 'footer'],
        ]
    );
});
