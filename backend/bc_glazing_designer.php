<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if ($mode == 'manage') {
    
    $glass = fn_bc_get_glass();
    $magic_hash = fn_bc_get_security_hash();
    fn_set_session_data('magic_hash', $magic_hash);
    Tygh::$app['view']->assign('magic_hash', $magic_hash);
    Tygh::$app['view']->assign('glass', $glass);
}


if ($_SERVER['REQUEST_METHOD']	== 'POST') {

    $suffix = '.manage';
    $controller = 'bc_glazing_designer';
    /**
     * Add glass
     */
    if ($mode == 'add') {
        if(!empty($_REQUEST['glass_data'])) {
            fn_bc_save_glass($_REQUEST['glass_data']);
        }
    }
    
    /**
     * Update from manage page
     */
    if ($mode == 'update') {
        if(!empty($_REQUEST['glass_data'])) {
            fn_bc_update_glass($_REQUEST['glass_data']);
        }        
    }
        
    /**
     * Delete glass
     */
    if ($mode == 'delete') {
        if(isset($_REQUEST['magic_hash'], $_REQUEST['glass_id'])) {
            
            $magic_hash = fn_get_session_data('magic_hash');
            if(!empty($magic_hash) && $magic_hash == $_REQUEST['magic_hash']) {
                fn_bc_delete_glass($_REQUEST['glass_id']);
            }
        }
    }
    
    /**
     * Delete glazing
     */
    if ($mode == 'g_delete') {
        if(isset($_REQUEST['glazing_designer_hash'], $_REQUEST['glazing_id'])) {
            
            $designer_hash = fn_get_session_data('glazing_designer_hash');
            if(!empty($designer_hash) && $designer_hash == $_REQUEST['glazing_designer_hash']) {
                fn_bc_delete_glazing($_REQUEST['glazing_id']);
            }
        }
        if(isset($_REQUEST['product_id'])) {
            $controller = 'products';
            $suffix = '.update&product_id='. $_REQUEST['product_id'] .'&selected_section=glazing_designer';
        } else {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
    }
    
    /**
     * import csv
     */
    if ($mode == 'import') {
        $file = fn_filter_uploaded_data('csv_file');

        if (!empty($file)) {
            if (empty($_REQUEST['pattern_id'])) {
                fn_set_notification('E', __('error'), __('error_exim_pattern_not_found'));
            } else {
                $pattern = fn_exim_get_pattern_definition($_REQUEST['pattern_id'], 'import');

                if (($data = fn_exim_get_csv($pattern, $file[0]['path'], $_REQUEST['import_options'])) != false) {
                    fn_import($pattern, $data, $_REQUEST['import_options']);
                }
            }
        } else {
            fn_set_notification('E', __('error'), __('error_exim_no_file_uploaded'));
        }
        exit;
    }

    return array(CONTROLLER_STATUS_OK, $controller . $suffix);
}

/**
 * export csv
 */
if ($mode == 'export') {

    if(isset($_REQUEST['product_id'])) {
        $product_id = $_REQUEST['product_id'];
        $data = fn_bc_get_product_data_export($product_id);
        
        if($data && !empty($data['prduct_name'])) {
            
            $filename = str_replace(' ', '_', fn_rusLat($data['prduct_name'])) . '_' . date('dmY'). '.csv';
            unset($data['prduct_name']);

            $result = [];
            $combination = '';
            
            foreach($data as $k => $v) {
                $combination = isset($v['glazing_name'])? $v['glazing_name'] : '';
                $combination .= (isset($v['treatment']) && $v['treatment'] != '')? ' - ' . htmlspecialchars($v['treatment']) : '';
                $combination .= (isset($v['title']) && $v['title'] != '') ? ' - ' . htmlspecialchars($v['title']) : '';
                $combination .= (isset($v['attr']) && $v['attr'] != '')? ' - ' . htmlspecialchars($v['attr']) : '';

                $result[] = [
                    'Combination' => $combination,
                    'Price' => $v['price'],
                    'Glazing id' => $v['glazing_id']
                ];
            }
            $options = [
                'delimiter' => 'S',
                'filename' => $filename
            ];
            $enclosure = '"';

            $res = fn_exim_put_csv($result, $options, $enclosure);

            if($res) {
                fn_set_notification('N', __('notice'), __('text_success_exported'));
            }
            
            // Direct download
            if ($_REQUEST['output'] == 'D') {
                $url = fn_url("bc_glazing_designer.get_file?filename=" . rawurlencode($filename), 'A', 'current');
            }
            
            if (defined('AJAX_REQUEST') && !empty($url)) {
                Tygh::$app['ajax']->assign('force_redirection', $url);

                exit;
            }

            $url = 'products.update&product_id='. $product_id .'&selected_section=glazing_designer';
        }
    } else {
        $url = 'products.manage';
    }
    
    return array(CONTROLLER_STATUS_OK,  $url);
}

/**
 * import csv
 */
if ($mode == 'import') {
    if(isset($_REQUEST['product_id'])) {
        Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
    }
}

// Скачивание файла
if ($mode == 'get_file' && !empty($_REQUEST['filename'])) {
    $file = fn_basename($_REQUEST['filename']);

    fn_get_file(fn_get_files_dir_path() . $file);
}