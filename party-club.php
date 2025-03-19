<?php

/**
 * Plugin Name: Party Club
 * Plugin URI:  https://norasinc.jp/
 * Description: イベント参加機能を提供するプラグインです。カスタム投稿タイプ「イベント」と「イベント登録」を作成し、ショートコードでフロントエンドからイベント参加登録（トグル）が行えます。
 * Version:     1.0
 * Author:      noras inc.
 * Author URI:  https://norasinc.jp/
 * License:     GPL2
 * Text Domain: party-club
 * Domain Path: /languages
 */

// 直接アクセスを防止
if (! defined('ABSPATH')) {
    exit;
}

/*
 * [イベント] 投稿タイプを追加
 */
function party_club_register_event_post_type()
{
    $labels = array(
        'name'               => __('イベント', 'party-club'),
        'singular_name'      => __('イベント', 'party-club'),
        'menu_name'          => __('イベント', 'party-club'),
        'name_admin_bar'     => __('イベント', 'party-club'),
        'add_new'            => __('新規追加', 'party-club'),
        'add_new_item'       => __('新しいイベントを追加', 'party-club'),
        'new_item'           => __('新規イベント', 'party-club'),
        'edit_item'          => __('イベントを編集', 'party-club'),
        'view_item'          => __('イベントを表示', 'party-club'),
        'all_items'          => __('すべてのイベント', 'party-club'),
        'search_items'       => __('イベントを検索', 'party-club'),
        'not_found'          => __('イベントが見つかりません', 'party-club'),
        'not_found_in_trash' => __('ゴミ箱にイベントはありません', 'party-club'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'supports'           => array('title', 'thumbnail', 'author', 'comments'),
        'capability_type'    => array('event', 'events'),
        'map_meta_cap'       => true,
        'show_in_rest'       => true,
    );
    register_post_type('event', $args);
}
add_action('init', 'party_club_register_event_post_type');

/**
 * 投稿タイプ event_registration を作成
 */
function party_club_register_event_registration_post_type()
{
    $labels = array(
        'name'                  => _x('Event Registrations', 'Post Type General Name', 'party-club'),
        'singular_name'         => _x('Event Registration', 'Post Type Singular Name', 'party-club'),
        'menu_name'             => __('Event Registrations', 'party-club'),
        'name_admin_bar'        => __('Event Registration', 'party-club'),
        'archives'              => __('Event Registration Archives', 'party-club'),
        'attributes'            => __('Registration Attributes', 'party-club'),
        'parent_item_colon'     => __('Parent Registration:', 'party-club'),
        'all_items'             => __('All Registrations', 'party-club'),
        'add_new_item'          => __('Add New Registration', 'party-club'),
        'add_new'               => __('Add New', 'party-club'),
        'new_item'              => __('New Registration', 'party-club'),
        'edit_item'             => __('Edit Registration', 'party-club'),
        'update_item'           => __('Update Registration', 'party-club'),
        'view_item'             => __('View Registration', 'party-club'),
        'view_items'            => __('View Registrations', 'party-club'),
        'search_items'          => __('Search Registration', 'party-club'),
        'not_found'             => __('Not found', 'party-club'),
        'not_found_in_trash'    => __('Not found in Trash', 'party-club'),
        'featured_image'        => __('Featured Image', 'party-club'),
        'set_featured_image'    => __('Set featured image', 'party-club'),
        'remove_featured_image' => __('Remove featured image', 'party-club'),
        'use_featured_image'    => __('Use as featured image', 'party-club'),
        'insert_into_item'      => __('Insert into registration', 'party-club'),
        'uploaded_to_this_item' => __('Uploaded to this registration', 'party-club'),
        'items_list'            => __('Registrations list', 'party-club'),
        'items_list_navigation' => __('Registrations list navigation', 'party-club'),
        'filter_items_list'     => __('Filter registrations list', 'party-club'),
    );

    $args = array(
        'label'                 => __('Event Registration', 'party-club'),
        'description'           => __('Post type for event registrations', 'party-club'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'custom-fields'),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => false,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-calendar',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
    );
    register_post_type('event_registration', $args);
}
add_action('init', 'party_club_register_event_registration_post_type', 0);

/**
 * プラグイン有効化時の処理
 */
function party_club_activate()
{
    party_club_register_event_post_type();
    party_club_register_event_registration_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'party_club_activate');

/**
 * プラグイン無効化時の処理
 */
function party_club_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'party_club_deactivate');

/**
 * REST API エンドポイント: イベント参加（トグル）処理
 *
 * POST パラメータ:
 *   - event_id: 参加対象のイベント投稿ID（数値）
 *
 * ユーザーが既に参加していれば登録解除し、未参加であれば登録するトグル処理を行います。
 */
function party_club_toggle_registration_endpoint(WP_REST_Request $request)
{
    if (! is_user_logged_in()) {
        return new WP_Error('not_logged_in', __('ログインが必要です。', 'party-club'), array('status' => 401));
    }
    $user_id = get_current_user_id();
    $event_id = intval($request->get_param('event_id'));
    if (! $event_id) {
        return new WP_Error('invalid_event', __('無効なイベントIDです。', 'party-club'), array('status' => 400));
    }
    $event_post = get_post($event_id);
    if (! $event_post || $event_post->post_type !== 'event') {
        return new WP_Error('event_not_found', __('対象のイベントが見つかりません。', 'party-club'), array('status' => 404));
    }
    // 重複登録の有無をチェック
    $args = array(
        'post_type'  => 'event_registration',
        'meta_query' => array(
            array(
                'key'     => 'user_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    if ($query->found_posts > 0) {
        // 登録済みの場合は削除（登録解除）
        foreach ($query->posts as $registration_id) {
            wp_delete_post($registration_id, true);
        }
        return rest_ensure_response(array(
            'success'       => true,
            'unregistered'  => true,
        ));
    } else {
        // 未登録の場合は登録
        $registration_data = array(
            'post_title'  => sprintf(__('Event %d Registration by User %d', 'party-club'), $event_id, $user_id),
            'post_type'   => 'event_registration',
            'post_status' => 'publish',
        );
        $registration_id = wp_insert_post($registration_data);
        if (is_wp_error($registration_id)) {
            return new WP_Error('registration_failed', __('登録処理に失敗しました。', 'party-club'), array('status' => 500));
        }
        update_post_meta($registration_id, 'user_id', $user_id);
        update_post_meta($registration_id, 'event_id', $event_id);
        update_post_meta($registration_id, 'registration_date', current_time('mysql'));
        return rest_ensure_response(array(
            'success'       => true,
            'registered'    => true,
            'registration_id' => $registration_id,
        ));
    }
}
add_action('rest_api_init', function () {
    register_rest_route('party-club/v1', '/toggle-registration', array(
        'methods'             => 'POST',
        'callback'            => 'party_club_toggle_registration_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'args' => array(
            'event_id' => array(
                'required'          => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ),
        ),
    ));
});

/**
 * ショートコード: イベント参加ボタン（トグル）の出力
 *
 * 使用例: [party_club_event_participation event_id="123"]
 */
function party_club_event_participation_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'event_id' => 0,
    ), $atts, 'party_club_event_participation');

    $event_id = intval($atts['event_id']);
    if (! $event_id) {
        return '<p>' . __('無効なイベントIDです。', 'party-club') . '</p>';
    }

    // ログインしていない場合は、参加ボタンをリンク付きで表示（クリックするとログインページへ）
    if (! is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return '<a href="' . esc_url($login_url) . '" class="party-club-participate-button">' . __('参加する', 'party-club') . '</a>';
    }

    $user_id = get_current_user_id();
    // 登録状態をチェック
    $args = array(
        'post_type'  => 'event_registration',
        'meta_query' => array(
            array(
                'key'     => 'user_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    $registered = ($query->found_posts > 0);
    wp_reset_postdata();
    $state_class = $registered ? ' is_registered' : '';
    $button_text = $registered ? __('登録済み', 'party-club') : __('参加する', 'party-club');
    // コンテナでラップして、data-event-id 属性とメッセージ表示用 span を出力
    return '
    <div class="party-club-participation" data-event-id="' . esc_attr($event_id) . '">
        <button class="party-club-participate-button ' . $state_class . ' "  data-event-id="' . esc_attr($event_id) . '">' . $button_text . '</button>
        <span class="party-club-participation-message"></span>
    </div>';
}
add_shortcode('party_club_event_participation', 'party_club_event_participation_shortcode');

/**
 * ショートコード: イベント参加者一覧の出力
 *
 * 使用例: [party_club_participants event_id="123"]
 */
function party_club_display_participants_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'event_id' => 0,
    ), $atts, 'party_club_participants');

    $event_id = intval($atts['event_id']);
    if (! $event_id) {
        return '<p>' . __('無効なイベントIDです。', 'party-club') . '</p>';
    }

    $args = array(
        'post_type'      => 'event_registration',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
    );
    $registrations = new WP_Query($args);
    if (! $registrations->have_posts()) {
        return '<p class="text-tetote-textBlackMuted">' . __('参加者はまだいません', 'party-club') . '</p>';
    }

    $output = '<ul class="party-club-participants">';
    while ($registrations->have_posts()) {
        $registrations->the_post();
        $user_id = get_post_meta(get_the_ID(), 'user_id', true);
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $output .= '<li>' . esc_html($user->display_name) . '</li>';
            }
        }
    }
    wp_reset_postdata();
    $output .= '</ul>';
    return $output;
}
add_shortcode('party_club_participants', 'party_club_display_participants_shortcode');

/**
 * 指定のイベントに対して、現在のユーザーが参加登録済みかどうかを判定する
 *
 * @param int $event_id イベント投稿ID
 * @param int $user_id  ユーザーID（省略時は現在のユーザー）
 * @return bool         参加済みなら true、未登録なら false
 */
function party_club_is_user_registered($event_id, $user_id = 0)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }
    if (! $user_id) {
        return false;
    }
    $args = array(
        'post_type'  => 'event_registration',
        'meta_query' => array(
            array(
                'key'     => 'user_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields' => 'ids',
    );
    $query = new WP_Query($args);
    return ($query->found_posts > 0);
}

/**
 * フロントエンド用スクリプトの読み込み
 */
function party_club_enqueue_scripts()
{
    wp_enqueue_script(
        'party-club-script',
        plugin_dir_url(__FILE__) . 'assets/js/party-club.js',
        array(),
        '1.0',
        true
    );
    wp_localize_script('party-club-script', 'partyClubSettings', array(
        'root'  => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
    ));
}
add_action('wp_enqueue_scripts', 'party_club_enqueue_scripts');

/**
 * 指定のイベントに登録している参加者数を取得する
 *
 * @param int $event_id イベント投稿ID
 * @return int 参加者数
 */
function party_club_get_participant_count($event_id)
{
    $args = array(
        'post_type'      => 'event_registration',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields'         => 'ids',
    );
    $query = new WP_Query($args);
    $count = $query->found_posts;
    wp_reset_postdata();
    return $count;
}

/**
 * ショートコード: 参加者数を出力
 *
 * 使用例: [party_club_participant_count event_id="123"]
 */
function party_club_participant_count_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'event_id' => 0,
    ), $atts, 'party_club_participant_count');

    $event_id = intval($atts['event_id']);
    if (! $event_id) {
        return '';
    }

    $count = party_club_get_participant_count($event_id);
    return $count;
}
add_shortcode('party_club_participant_count', 'party_club_participant_count_shortcode');


/**
 * イベント編集画面に参加予定者一覧のメタボックスを追加
 */
function party_club_add_event_participants_meta_box()
{
    global $post;
    // 投稿が存在していて、かつ auto-draft の場合はメタボックスを追加しない
    if (isset($post) && 'auto-draft' === $post->post_status) {
        return;
    }
    add_meta_box(
        'party_club_event_participants', // メタボックスID
        __('このイベントに参加予定のユーザー', 'party-club'), // タイトル
        'party_club_event_participants_meta_box_callback', // コールバック関数
        'event', // 対象の投稿タイプ
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'party_club_add_event_participants_meta_box');


/**
 * 参加予定者一覧を表示するメタボックスのコールバック関数
 *
 * @param WP_Post $post 現在のイベント投稿
 */
function party_club_event_participants_meta_box_callback($post)
{

    // 新規追加時（auto-draft または投稿IDがない場合）は表示しない
    if ('auto-draft' === $post->post_status || ! $post->ID) {
        return;
    }

    $event_id = $post->ID;

    // event_registration 投稿タイプから、meta_key 'event_id' が一致する投稿を取得
    $args = array(
        'post_type'      => 'event_registration',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields'         => 'ids',
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<ul style="list-style: none; padding: 0;">';
        foreach ($query->posts as $registration_id) {
            // 各登録から user_id を取得
            $user_id = get_post_meta($registration_id, 'user_id', true);
            if ($user_id) {
                $user = get_userdata($user_id);
                if ($user) {
                    // ユーザーのアバター（32pxサイズ）、表示名、メールアドレスをクリック可能に（mailtoリンク）表示
                    $avatar   = get_avatar($user_id, 32);
                    $email    = sprintf('<a href="mailto:%s">%s</a>', esc_attr($user->user_email), esc_html($user->user_email));
                    echo '<li style="margin-bottom: 8px; display: flex; align-items: center;">';
                    echo $avatar;
                    echo '<div style="margin-left: 8px;">';
                    echo '<strong>' . esc_html($user->display_name) . '</strong><br>';
                    echo $email;
                    echo '</div>';
                    echo '</li>';
                }
            }
        }
        echo '</ul>';
        // ボタン（CSVエクスポート、参加者編集、一斉メール送信）を横並びで出力
        echo '<p>';
        echo '<a href="' . esc_url(admin_url('admin-post.php?action=party_club_export_participants&event_id=' . $event_id)) . '" class="button button-primary">' . __('参加者をCSVでエクスポート', 'party-club') . '</a> ';
        echo '<a href="' . esc_url(admin_url('edit.php?post_type=event&page=party-club-participants&event_id=' . $event_id)) . '" class="button">' . __('参加者を編集', 'party-club') . '</a> ';
        echo '<a href="' . esc_url(admin_url('admin.php?page=party_club_mass_email&event_id=' . $event_id)) . '" class="button button-secondary">' . __('一斉メール送信', 'party-club') . '</a>';
        echo '</p>';
    } else {
        echo '<p>' . __('参加予定のユーザーはいません。', 'party-club') . '</p>';
    }
    wp_reset_postdata();
}


/**
 * 参加者一覧をCSV出力する（管理画面用）
 */
function party_club_export_participants()
{
    if (! current_user_can('edit_posts')) {
        wp_die(__('アクセス権がありません。', 'party-club'));
    }
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    if (! $event_id) {
        wp_die(__('無効なイベントIDです。', 'party-club'));
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=participants_event_' . $event_id . '.csv');

    $output = fopen('php://output', 'w');
    // ヘッダー行を出力
    fputcsv($output, array('Display Name', 'Email'));

    $args = array(
        'post_type'      => 'event_registration',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields'         => 'ids',
    );
    $query = new WP_Query($args);
    foreach ($query->posts as $registration_id) {
        $user_id = get_post_meta($registration_id, 'user_id', true);
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                fputcsv($output, array($user->display_name, $user->user_email));
            }
        }
    }
    fclose($output);
    exit;
}
add_action('admin_post_party_club_export_participants', 'party_club_export_participants');


/**
 * イベント投稿の管理画面にサブメニュー「参加者編集」を追加
 */
function party_club_add_participants_submenu()
{
    add_submenu_page(
        'edit.php?post_type=event', // 親：イベント投稿の管理画面
        __('イベント参加者編集', 'party-club'),
        __('参加者編集', 'party-club'),
        'edit_posts',
        'party-club-participants',
        'party_club_render_participants_page'
    );
    // 登録したサブメニューを管理画面から非表示にする
    remove_submenu_page('edit.php?post_type=event', 'party-club-participants');
}
add_action('admin_menu', 'party_club_add_participants_submenu');

/**
 * 参加者一覧画面を出力するコールバック
 */
function party_club_render_participants_page()
{
    if (! isset($_GET['event_id'])) {
        echo '<div class="wrap"><h1>' . __('イベント参加者編集', 'party-club') . '</h1>';
        echo '<p>' . __('イベントIDが指定されていません。', 'party-club') . '</p></div>';
        return;
    }
    $event_id = intval($_GET['event_id']);

    echo '<div class="wrap"><h1>' . __('イベント参加者編集', 'party-club') . '</h1>';
    $event_title = get_the_title($event_id);
    echo '<h2>' . sprintf(__('「%s」の参加者一覧', 'party-club'), $event_title) . '</h2>';

    $args = array(
        'post_type'      => 'event_registration',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields'         => 'ids',
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>' . __('アバター', 'party-club') . '</th><th>' . __('ユーザー名', 'party-club') . '</th><th>' . __('メールアドレス', 'party-club') . '</th><th>' . __('操作', 'party-club') . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($query->posts as $reg_id) {
            $user_id = get_post_meta($reg_id, 'user_id', true);
            if ($user_id) {
                $user = get_userdata($user_id);
                if ($user) {
                    $avatar = get_avatar($user_id, 32);
                    $mailto = sprintf('<a href="mailto:%s">%s</a>', esc_attr($user->user_email), esc_html($user->user_email));
                    $delete_url = wp_nonce_url(admin_url('admin-post.php?action=party_club_delete_registration&registration_id=' . $reg_id . '&event_id=' . $event_id), 'party_club_delete_registration');
                    echo '<tr>';
                    echo '<td>' . $avatar . '</td>';
                    echo '<td>' . esc_html($user->display_name) . '</td>';
                    echo '<td>' . $mailto . '</td>';
                    echo '<td><a href="' . esc_url($delete_url) . '" class="button">' . __('削除', 'party-club') . '</a></td>';
                    echo '</tr>';
                }
            }
        }
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('参加者はいません。', 'party-club') . '</p>';
    }
    echo '</div>';
    wp_reset_postdata();
}

function party_club_add_mass_email_submenu()
{
    add_submenu_page(
        null, // 親メニューに表示しない
        __('一斉メール送信', 'party-club'),
        __('一斉メール送信', 'party-club'),
        'edit_posts',
        'party_club_mass_email',
        'party_club_render_mass_email_page'
    );
}
add_action('admin_menu', 'party_club_add_mass_email_submenu');

function party_club_render_mass_email_page()
{
    if (! isset($_GET['event_id'])) {
        echo '<div class="wrap"><h1>' . __('一斉メール送信', 'party-club') . '</h1>';
        echo '<p>' . __('イベントIDが指定されていません。', 'party-club') . '</p></div>';
        return;
    }
    $event_id = intval($_GET['event_id']);
    $event_title = get_the_title($event_id);
?>
    <div class="wrap">
        <h1><?php _e('一斉メール送信', 'party-club'); ?></h1>
        <p><?php echo sprintf(__('「%s」の参加者にメールを送信します。', 'party-club'), $event_title); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('party_club_mass_email', 'party_club_mass_email_nonce'); ?>
            <input type="hidden" name="action" value="party_club_mass_email">
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="subject"><?php _e('件名', 'party-club'); ?></label></th>
                    <td><input name="subject" type="text" id="subject" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="message"><?php _e('本文', 'party-club'); ?></label></th>
                    <td><textarea name="message" id="message" rows="10" class="large-text code" required></textarea></td>
                </tr>
            </table>
            <?php submit_button(__('送信', 'party-club')); ?>
        </form>
    </div>
<?php
}


function party_club_handle_mass_email()
{
    if (! current_user_can('edit_posts')) {
        wp_die(__('権限がありません。', 'party-club'));
    }
    if (! isset($_POST['party_club_mass_email_nonce']) || ! wp_verify_nonce($_POST['party_club_mass_email_nonce'], 'party_club_mass_email')) {
        wp_die(__('セキュリティチェックに失敗しました。', 'party-club'));
    }
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if (! $event_id) {
        wp_die(__('無効なイベントIDです。', 'party-club'));
    }
    $subject = sanitize_text_field($_POST['subject']);
    $message = wp_kses_post($_POST['message']);

    // 対象イベントの参加者メールアドレスを収集
    $args = array(
        'post_type'      => 'event_registration',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_id',
                'value'   => $event_id,
                'compare' => '=',
            ),
        ),
        'fields'         => 'ids',
    );
    $query = new WP_Query($args);
    $emails = array();
    foreach ($query->posts as $reg_id) {
        $user_id = get_post_meta($reg_id, 'user_id', true);
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user && is_email($user->user_email)) {
                $emails[] = $user->user_email;
            }
        }
    }
    wp_reset_postdata();

    // メール送信（ここではBCCを利用）
    $headers = array('BCC: ' . implode(',', $emails));
    $from_email = get_bloginfo('admin_email');
    $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $from_email . '>';

    $sent = wp_mail($from_email, $subject, $message, $headers);
    if ($sent) {
        wp_redirect(admin_url('edit.php?post_type=event&page=party-club-participants&event_id=' . $event_id . '&mass_email=success'));
        exit;
    } else {
        wp_redirect(admin_url('edit.php?post_type=event&page=party-club-participants&event_id=' . $event_id . '&mass_email=failed'));
        exit;
    }
}
add_action('admin_post_party_club_mass_email', 'party_club_handle_mass_email');


// イベント投稿のパーマリンクを変更

function party_club_event_permalink($post_link, $post, $leavename)
{
    if ('event' === $post->post_type) {
        return home_url('/' . $post->ID . '/');
    }
    return $post_link;
}
add_filter('post_type_link', 'party_club_event_permalink', 10, 3);

function party_club_event_rewrite_rules()
{
    add_rewrite_rule('^([0-9]+)/?$', 'index.php?post_type=event&p=$matches[1]', 'top');
}
add_action('init', 'party_club_event_rewrite_rules');

/**
 * 管理画面の投稿一覧から、イベント投稿の編集リンク（通常・クイック編集）を削除する
 */
function party_club_remove_event_edit_links($actions, $post)
{
    if ('event' === $post->post_type) {
        unset($actions['edit']);
        unset($actions['inline hide-if-no-js']); // クイック編集のリンク
        unset($actions['quick_edit']);
    }
    return $actions;
}
add_filter('post_row_actions', 'party_club_remove_event_edit_links', 10, 2);
