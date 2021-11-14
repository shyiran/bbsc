<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;

/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class Flea extends BaseFlea {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/flea.lang.php');
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/fleacommon.lang.php');
    }

    /**
     * 闲置市场首页
     */
    public function index() {

        /**
         * 地区
         */
        $fleaarea_model = model('fleaarea');
        $area_array = $fleaarea_model->fleaarea_show();
        View::assign('area_one_level', $area_array['area_one_level']);
        View::assign('area_two_level', $area_array['area_two_level']);
        /**
         * 分类
         */
        
        $fleaclass_model = model('fleaclass');
        $goods_class = $fleaclass_model->getTreeClassList(3, array('fleaclass_show' => 1));
        if (is_array($goods_class) and !empty($goods_class)) {
            $show_goods_class = array();
            $arr = array();
            foreach ($goods_class as $val) {
                if ($val['fleaclass_parent_id'] == 0) {
                    $show_goods_class[$val['fleaclass_id']]['class_name'] = $val['fleaclass_name'];
                    $show_goods_class[$val['fleaclass_id']]['class_id'] = $val['fleaclass_id'];
                    $show_goods_class[$val['fleaclass_id']]['fleaclass_index_show'] = $val['fleaclass_index_show'];

                    $arr[$val['fleaclass_id']]['class_name'] = $val['fleaclass_name'];
                    $arr[$val['fleaclass_id']]['class_id'] = $val['fleaclass_id'];
                    if (!isset($arr[$val['fleaclass_id']]['fleaclass_id_str'])) {
                        $arr[$val['fleaclass_id']]['fleaclass_id_str'] = '';
                    }
                    $arr[$val['fleaclass_id']]['fleaclass_id_str'] .= ',' . $val['fleaclass_id'];
                } else {
                    if (isset($show_goods_class[$val['fleaclass_parent_id']])) {
                        $show_goods_class[$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['class_name'] = $val['fleaclass_name'];
                        $show_goods_class[$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['class_id'] = $val['fleaclass_id'];
                        $show_goods_class[$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['fleaclass_parent_id'] = $val['fleaclass_parent_id'];
                        $show_goods_class[$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['fleaclass_index_show'] = $val['fleaclass_index_show'];

                        $arr[$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['class_name'] = $val['fleaclass_name'];
                        $arr[$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['class_id'] = $val['fleaclass_id'];
                        $arr[$val['fleaclass_parent_id']]['fleaclass_id_str'] .= ',' . $val['fleaclass_id'];
                    } else {
                        foreach ($show_goods_class as $v) {
                            if (isset($v['sub_class'][$val['fleaclass_parent_id']])) {
                                $show_goods_class[$v['sub_class'][$val['fleaclass_parent_id']]['fleaclass_parent_id']]['sub_class'][$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['class_name'] = $val['fleaclass_name'];
                                $show_goods_class[$v['sub_class'][$val['fleaclass_parent_id']]['fleaclass_parent_id']]['sub_class'][$val['fleaclass_parent_id']]['sub_class'][$val['fleaclass_id']]['class_id'] = $val['fleaclass_id'];
                                $arr[$v['sub_class'][$val['fleaclass_parent_id']]['fleaclass_parent_id']]['fleaclass_id_str'] .= ',' . $val['fleaclass_id'];
                            }
                        }
                    }
                }
            }
        }

        $new_arr = array();
        $flea_model = model('flea');
        $condition = array();
        $condition[]=array('flea.goods_image','<>','');
        if (isset($arr) && !empty($arr)) {
            foreach ($arr as $key => $value) {
                if (isset($new_arr[4]) && is_array($new_arr[4]) && !empty($new_arr[4]))
                    break; //只取前5条分类下有的商品
                $condition[]=array('flea.fleaclass_id','in',$value['fleaclass_id_str']);
                $arr[$key]['goods'] = $flea_model->getFleaByClass($condition,'goods_id,goods_name,goods_store_price,flea_quality,member_id,goods_image','goods_id desc',14);
                if (is_array($arr[$key]['goods']) && !empty($arr[$key]['goods']))
                    $new_arr[] = $arr[$key];
            }
        }
        View::assign('show_flea_goods_class_list', $new_arr);
        /**
         * js滑动参数
         */
        $str = '';
        $str1 = '';
        for ($j = 1; $j <= count($new_arr); $j++) {
            $str .= '"m0' . $j . '"' . ',';
            $str1 .= '"c0' . $j . '"' . ',';
        }
        $str = rtrim($str, ",");
        $str1 = rtrim($str1, ",");
        View::assign('mstr', $str);
        View::assign('cstr', $str1);
        /**
         * 新鲜货
         */
        $condition = array();
        $condition[]=array('goods_image','<>','');
        $condition[]=array('goods_body','<>','');
        $new_flea_goods = $flea_model->getOneFlea($condition);
        View::assign('new_flea_goods', $new_flea_goods);
        /**
         * 收藏第一
         */
        $condition = array();
        $condition[]=array('goods_image','<>','');
        $col_flea_goods = $flea_model->getOneFlea($condition);
        View::assign('col_flea_goods', $col_flea_goods);
        /**
         * 热门搜
         */
        $new_flea_goods2 = $flea_model->getFleaList(array('pic_input' => '2'),'','*','goods_click desc',14);
        View::assign('new_flea_goods2', $new_flea_goods2);
        /**
         * 闲置围观区
         */
        $new_flea_goods3 = $flea_model->getFleaList(array('pic_input' => '2'),'','*','goods_id desc',14);
        View::assign('new_flea_goods3', $new_flea_goods3);
        /**
         * 导航标识
         */
        View::assign('index_sign', 'flea');
        // 首页幻灯
        $loginpic = unserialize(config('ds_config.flea_loginpic'));
        View::assign('loginpic', $loginpic);
        
        /**
         * 广告图
         */
        $result = false;
        $condition = array();
        $condition_1 = array();
        $condition_2 = array();
        $condition_3 = array();
        $condition_4 = array();
        $condition_5 = array();
        $condition_1[] = ['ap_id', '=', 16];
        $condition_2[] = ['ap_id', '=', 17];
        $condition_3[] = ['ap_id', '=', 18];
        $condition_4[] = ['ap_id', '=', 19];
        $condition_5[] = ['ap_id', '=', 20];
        $condition[] = ['adv_enabled', '=', 1];
        $condition[] = ['adv_startdate', '<', strtotime(date('Y-m-d H:00:00'))];
        $condition[] = ['adv_enddate', '>', strtotime(date('Y-m-d H:00:00'))];
        $adv_list = model('adv')->getAdvList(array_merge($condition,$condition_1), '', 10, 'adv_sort asc,adv_id asc');
        if (!empty($adv_list)) {
            $result = $adv_list;
        }
        $adv_four=array();
        $adv_list = model('adv')->getAdvList(array_merge($condition,$condition_2), '', 1, 'adv_sort asc,adv_id asc');
        if (!empty($adv_list)) {
            $adv_four[] = $adv_list[0];
        }
        $adv_list = model('adv')->getAdvList(array_merge($condition,$condition_3), '', 1, 'adv_sort asc,adv_id asc');
        if (!empty($adv_list)) {
            $adv_four[] = $adv_list[0];
        }
        $adv_list = model('adv')->getAdvList(array_merge($condition,$condition_4), '', 1, 'adv_sort asc,adv_id asc');
        if (!empty($adv_list)) {
            $adv_four[] = $adv_list[0];
        }
        $adv_list = model('adv')->getAdvList(array_merge($condition,$condition_5), '', 1, 'adv_sort asc,adv_id asc');
        if (!empty($adv_list)) {
            $adv_four[] = $adv_list[0];
        }
        
        View::assign('adv_slide', $result);
        View::assign('adv_four', $adv_four);
        /**
         * 获取设置信息
         */
        $fleaclass_model = model('fleaclass');
        $fc_index = $fleaclass_model->getFleaclassindex(array());
        if (!empty($fc_index) && is_array($fc_index)) {
            foreach ($fc_index as $value) {
                View::assign($value['fcindex_code'], $value);
            }
        }
        //SEO 设置
        $seo = array(
            'html_title'=>config('ds_config.flea_site_title'),
            'seo_keywords'=>config('ds_config.flea_site_keywords'),
            'seo_description'=>config('ds_config.flea_site_description'),
            
        );
        $this->_assign_seo($seo);
        
        return View::fetch($this->template_dir . 'flea_index');
    }

}
