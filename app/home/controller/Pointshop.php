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
class Pointshop extends BasePointShop
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path().'home/lang/'.config('lang.default_lang').'/pointprod.lang.php');
        Lang::load(base_path().'home/lang/'.config('lang.default_lang').'/voucher.lang.php');
    }
    public function index(){
        //查询会员及其附属信息
        parent::pointshopMInfo();

        //开启代金券功能后查询推荐的热门代金券列表
        if (config('ds_config.voucher_allow') == 1){
            $recommend_voucher = model('voucher')->getRecommendTemplate(6);
            View::assign('recommend_voucher',$recommend_voucher);
        }
        //开启积分兑换功能后查询推荐的热门兑换商品列表
        if (config('ds_config.pointprod_isuse') == 1){
            //热门积分兑换商品
            $recommend_pointsprod = model('pointprod')->getRecommendPointProd(10);
            View::assign('recommend_pointsprod',$recommend_pointsprod);
        }

        //SEO
        $this->_assign_seo(model('seo')->type('point')->show());
        //分类导航
        $nav_link = array(
            0=>array('title'=>lang('homepage'),'link'=>HOME_SITE_URL),
            1=>array('title'=>lang('ds_pointprod'))
        );
        View::assign('nav_link_list', $nav_link);
        return View::fetch($this->template_dir.'pointprod');
    }

}