<?php
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view') {    
    
    $product_id = empty($_REQUEST['product_id']) ? 0 : $_REQUEST['product_id'];

    Registry::set('navigation.tabs.bc_glazing_designer', array (
                'title' => __('types_glazing'),
                'js' => true
            ));
    
    if($product_id) {
        $glazing_data = fn_bc_get_product_glazing_data($product_id, 'INNER', 'ORDER BY glazing_name, attr');
        
        $titles = [];
        $f = [];
        $addH = 0;
        $col_span = [];
        $treatments = [];
      
        foreach($glazing_data as $glazing) {
            foreach($glazing as $k => $v) {
                
                // Формируется массив для заголовка таблицы
                if($k == 'title') {

                    if(isset($titles[$v])) { // Есть такой title
                 
                        if(!in_array($glazing['attr'], $titles[$v]['attr'])) { // Нет такого attr у такого title
                            
                            $attr = $glazing['attr'];                            
  
                           if(($attr == '' && !isset($f[$v])) || $attr != '') {
                                $titles[$v]['n'] = $titles[$v]['n'] + 1;

                                if($attr == '') {
                                    $f[$v] = 1;
                                } else {
                                    $addH = 1;
                                }
                                $titles[$v]['attr'][] = $attr;
                            }
                        }
                    } else {// Нет такого title
                        $attr = $glazing['attr'];
                       
                        if($attr == '') {
                            $f[$v] = 1;
                        } else {
                            $addH = 1;
                        }
                        $titles[$v] = ['n' => 1, 'attr' => [$attr]];
                        
                    }                    
                }
                // Формируется массив для тела таблицы
                if($k == 'glazing_name') {
                    if(isset($col_span[$v])) {
                        
                        // Если есть обработка
                        if(isset($treatments[$v][$glazing['treatment']])) {
                            
                            // Если есть стекло
                            if(isset($treatments[$v][$glazing['treatment']][$glazing['title']])) {
                                
                                $treatments[$v][$glazing['treatment']][$glazing['title']][$glazing['attr']] = $glazing['price'];
                            
                            } else { // Если нет стекла
                                $treatments[$v][$glazing['treatment']][$glazing['title']] = [$glazing['attr'] => $glazing['price']];
                            }
                            
                        } else {
                            $treatments[$v][$glazing['treatment']] = [
                                $glazing['title'] => [
                                    $glazing['attr'] => $glazing['price']
                                ]
                            ];
                            ++$col_span[$v];
                        }
                    } else { // Первый вид остекления
                        $col_span[$v] = 1;
                        $treatments[$v][$glazing['treatment']] = [
                            $glazing['title'] => [
                                $glazing['attr'] => $glazing['price']
                            ]
                        ];
                    }                    
                }
            }
        }

        $header = ['headers' => $titles, 'addH' => $addH, 'col_span' => $col_span];
        Tygh::$app['view']->assign('index_glazing', $treatments);
        Tygh::$app['view']->assign('header', $header);
        Tygh::$app['view']->assign('glazing_data', $glazing_data);
    }
}