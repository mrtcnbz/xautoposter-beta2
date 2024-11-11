<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
?>

<div class="wrap xautoposter-wrap">
    <div class="xautoposter-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>
    
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(add_query_arg('tab', 'settings')); ?>" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('API Ayarları', 'xautoposter'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'auto-share')); ?>" 
           class="nav-tab <?php echo $current_tab === 'auto-share' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-clock"></span>
            <?php _e('Otomatik Paylaşım', 'xautoposter'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'manual-share')); ?>" 
           class="nav-tab <?php echo $current_tab === 'manual-share' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-share"></span>
            <?php _e('Manuel Paylaşım', 'xautoposter'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'metrics')); ?>" 
           class="nav-tab <?php echo $current_tab === 'metrics' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php _e('Metrikler', 'xautoposter'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php if ($current_tab === 'settings'): ?>
            <div class="xautoposter-card">
                <?php 
                $options = get_option('xautoposter_options', []);
                $api_verified = get_option('xautoposter_api_verified', false);
                
                if ($api_verified): ?>
                    <div class="api-status-bar">
                        <span class="status-text success">
                            <?php _e('API bağlantısı aktif', 'xautoposter'); ?>
                        </span>
                        <button type="button" id="unlock-api-settings" class="button button-secondary">
                            <?php _e('API Ayarlarını Düzenle', 'xautoposter'); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="options.php" id="api-settings-form">
                    <?php 
                    settings_fields('xautoposter_options_group');
                    do_settings_sections('xautoposter-settings');
                    submit_button(__('Ayarları Kaydet', 'xautoposter')); 
                    ?>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'auto-share'): ?>
            <div class="xautoposter-card">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('xautoposter_auto_share_options_group');
                    do_settings_sections('xautoposter-auto-share');
                    submit_button(__('Ayarları Kaydet', 'xautoposter'));
                    ?>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'manual-share'): ?>
            <div class="xautoposter-card">
                <div class="tablenav top">
                    <form method="get" class="filter-form">
                        <input type="hidden" name="page" value="xautoposter">
                        <input type="hidden" name="tab" value="manual-share">
                        
                        <select name="category" id="category-filter" class="postform">
                            <option value=""><?php _e('Tüm Kategoriler', 'xautoposter'); ?></option>
                            <?php 
                            $categories = get_categories(['hide_empty' => false]);
                            $selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
                            
                            foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>" 
                                        <?php selected($selected_category, $category->term_id); ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="order" id="date-sort">
                            <?php 
                            $sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
                            ?>
                            <option value="desc" <?php selected($sort_order, 'desc'); ?>>
                                <?php _e('En Yeni', 'xautoposter'); ?>
                            </option>
                            <option value="asc" <?php selected($sort_order, 'asc'); ?>>
                                <?php _e('En Eski', 'xautoposter'); ?>
                            </option>
                        </select>
                        
                        <?php submit_button(__('Filtrele', 'xautoposter'), 'secondary', '', false); ?>
                    </form>
                    
                    <div class="alignright">
                        <button type="button" id="share-selected" class="button button-primary" disabled>
                            <?php _e('Seçili Gönderileri Paylaş', 'xautoposter'); ?>
                        </button>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped posts-table">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="select-all-posts">
                            </th>
                            <th><?php _e('Görsel', 'xautoposter'); ?></th>
                            <th><?php _e('Başlık', 'xautoposter'); ?></th>
                            <th><?php _e('Kategori', 'xautoposter'); ?></th>
                            <th><?php _e('Tarih', 'xautoposter'); ?></th>
                            <th><?php _e('Durum', 'xautoposter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                        $posts_per_page = 10;
                        
                        $args = [
                            'post_type' => 'post',
                            'post_status' => 'publish',
                            'posts_per_page' => $posts_per_page,
                            'paged' => $paged,
                            'orderby' => 'date',
                            'order' => strtoupper($sort_order)
                        ];
                        
                        if ($selected_category) {
                            $args['cat'] = $selected_category;
                        }
                        
                        $query = new WP_Query($args);
                        
                        if ($query->have_posts()): 
                            while ($query->have_posts()): $query->the_post();
                                $post_id = get_the_ID();
                                $is_shared = get_post_meta($post_id, '_xautoposter_shared', true);
                                $share_time = get_post_meta($post_id, '_xautoposter_share_time', true);
                                $thumbnail = get_the_post_thumbnail_url($post_id, 'thumbnail');
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="posts[]" value="<?php echo esc_attr($post_id); ?>" 
                                           <?php disabled($is_shared, true); ?>>
                                </td>
                                <td>
                                    <?php if ($thumbnail): ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" 
                                             alt="<?php echo esc_attr(get_the_title()); ?>"
                                             width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="no-image"><?php _e('Görsel Yok', 'xautoposter'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link(); ?>">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $categories = get_the_category();
                                    $cat_names = array_map(function($cat) {
                                        return esc_html($cat->name);
                                    }, $categories);
                                    echo implode(', ', $cat_names);
                                    ?>
                                </td>
                                <td><?php echo get_the_date(); ?></td>
                                <td>
                                    <?php if ($is_shared): ?>
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php 
                                        echo sprintf(
                                            __('Paylaşıldı: %s', 'xautoposter'),
                                            wp_date(
                                                get_option('date_format') . ' ' . get_option('time_format'),
                                                strtotime($share_time)
                                            )
                                        );
                                        ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus"></span>
                                        <?php _e('Paylaşılmadı', 'xautoposter'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="6"><?php _e('Gönderi bulunamadı.', 'xautoposter'); ?></td>
                            </tr>
                        <?php 
                        endif;
                        wp_reset_postdata();
                        ?>
                    </tbody>
                </table>
                
                <?php if ($query->max_num_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $query->max_num_pages,
                                'current' => $paged,
                                'type' => 'list'
                            ]);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ($current_tab === 'metrics'): ?>
            <div class="xautoposter-card">
                <div class="metrics-grid">
                    <?php
                    $total_shares = count(get_posts([
                        'meta_key' => '_xautoposter_shared',
                        'meta_value' => '1',
                        'posts_per_page' => -1
                    ]));
                    
                    $total_engagement = 0;
                    $posts = get_posts([
                        'meta_key' => '_xautoposter_tweet_metrics',
                        'posts_per_page' => -1
                    ]);
                    
                    foreach ($posts as $post) {
                        $metrics = get_post_meta($post->ID, '_xautoposter_tweet_metrics', true);
                        if ($metrics && isset($metrics->public_metrics)) {
                            $total_engagement += $metrics->public_metrics->like_count + 
                                               $metrics->public_metrics->retweet_count + 
                                               $metrics->public_metrics->reply_count;
                        }
                    }
                    ?>
                    
                    <div class="metric-card">
                        <div class="metric-label"><?php _e('Toplam Paylaşım', 'xautoposter'); ?></div>
                        <div class="metric-value"><?php echo number_format($total_shares); ?></div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-label"><?php _e('Toplam Etkileşim', 'xautoposter'); ?></div>
                        <div class="metric-value"><?php echo number_format($total_engagement); ?></div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-label"><?php _e('Ortalama Etkileşim', 'xautoposter'); ?></div>
                        <div class="metric-value">
                            <?php 
                            echo number_format(
                                $total_shares > 0 ? $total_engagement / $total_shares : 0, 
                                1
                            ); 
                            ?>
                        </div>
                    </div>
                </div>
                
                <?php include(XAUTOPOSTER_PATH . 'templates/metrics-table.php'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>