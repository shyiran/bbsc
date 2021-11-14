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
class Memberinform extends BaseMember {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/memberinfom.lang.php');
    }

    /*
     * 获取当前用户的举报列表
     */

    public function index() {
        /*
         * 得到当前用户的举报列表
         */
        $inform_model = model('inform');
        $condition = array();
        $inform_state = intval(input('param.select_inform_state'));
        if($inform_state>0){
            $condition[] = array('inform.inform_state','=',$inform_state);
        }
        $condition[] = array('inform.inform_member_id','=',session('member_id'));
        $inform_list = $inform_model->getInformList($condition, 10);
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_inform');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('inform_list');
        View::assign('inform_list', $inform_list);
        View::assign('show_page', $inform_model->page_info->render());
        return View::fetch($this->template_dir . 'index');
    }

    /*
     * 提交举报商品
     */

    public function inform_submit() {

        //检查当前用户是否允许举报
        $this->check_member_allow_inform();

        $goods_id = intval(input('param.goods_id'));

        //获取商品详细信息
        $goods_info = $this->get_goods_info_byid($goods_id);

        //检查是否是本店商品
        if (!empty(session('store_id'))) {
            if ($goods_info['store_id'] == session('store_id')) {
                $this->error(lang('param_error'));
            }
        }

        $inform_model = model('inform');
        //检查是否当前正在举报
        if ($inform_model->isProcessOfInform($goods_id)) {
            $this->error(lang('inform_handling'));
        }

        //获取举报类型
        $informsubjecttype_model = model('informsubjecttype');
        $inform_subject_type_list = $informsubjecttype_model->getActiveInformsubjecttypeList();
        if (empty($inform_subject_type_list)) {
            $this->error(lang('inform_type_null'));
        }
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_inform');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('inform_list');

        View::assign('goods_info', $goods_info);
        View::assign('type_list', $inform_subject_type_list);
        return View::fetch($this->template_dir . 'inform_submit');
    }

    /*
     * 保存用户提交的商品举报
     */

    public function inform_save() {

        //检查当前用户是否允许举报
        $this->check_member_allow_inform();

        $goods_id = intval(input('post.inform_goods_id'));

        //获取商品详细信息
        $goods_info = $this->get_goods_info_byid($goods_id);

        //检查是否是本店商品
        if (!empty(session('store_id'))) {
            if ($goods_info['store_id'] == session('store_id')) {
                $this->error(lang('param_error'));
            }
        }

        //实例化举报模型
        $inform_model = model('inform');
        //检查是否当前正在举报
        if ($inform_model->isProcessOfInform($goods_id)) {
            $this->error(lang('inform_handling'));
        }
        //处理用户输入的数据
        $input = array();
        $input['inform_member_id'] = session('member_id');
        $input['inform_member_name'] = session('member_name');
        $input['inform_goods_id'] = $goods_id;
        $input['inform_goods_name'] = $goods_info['goods_name'];
        $input['inform_goods_image'] = $goods_info['goods_image'];
        list($input['informsubject_id'], $input['informsubject_content']) = explode(",", trim(input('post.inform_subject')));
        $input['inform_content'] = trim(input('post.inform_content'));

        //上传图片
        $inform_pic = array();
        $inform_pic[1] = 'inform_pic1';
        $inform_pic[2] = 'inform_pic2';
        $inform_pic[3] = 'inform_pic3';
        $pic_name = $this->inform_upload_pic($inform_pic);
        $input['inform_pic1'] = $pic_name[1];
        $input['inform_pic2'] = $pic_name[2];
        $input['inform_pic3'] = $pic_name[3];

        $input['inform_datetime'] = TIMESTAMP;
        $input['inform_store_id'] = $goods_info['store_id'];
        $input['inform_store_name'] = $goods_info['store_name'];
        $input['inform_state'] = 1;
        $input['inform_handle_message'] = '';
        $input['inform_handle_member_id'] = 0;
        $input['inform_handle_datetime'] = 1;

        //验证输入的数据
        $data = [
            'inform_content' => $input["inform_content"],
            'informsubject_content' => $input["informsubject_content"]
        ];

        $res=word_filter($input['inform_content']);
        if(!$res['code']){
            $this->error($res['msg']);
        }
        $input['inform_content']=$res['data']['text'];
        
        $inform_validate = ds_validate('inform');
        if (!$inform_validate->scene('inform_save')->check($data)) {
            $this->error($inform_validate->getError());
        }

        //保存
        if ($inform_model->addInform($input)) {
            $this->success(lang('inform_success'),(string)url('Memberinform/index'));
        } else {
            $this->error(lang('inform_fail'), (string)url('Memberinform/index'));
        }
    }

    /*
     * 取消用户提交的商品举报
     */

    public function inform_cancel() {

        $inform_id = intval(input('param.inform_id'));
        $inform_info = $this->get_inform_info($inform_id);

        if (intval($inform_info['inform_state']) === 1) {
            $pics = array();
            if (!empty($inform_info['inform_pic1'])) {
                $pics[] = $inform_info['inform_pic1'];
            }
            if (!empty($inform_info['inform_pic2'])) {
                $pics[] = $inform_info['inform_pic2'];
            }
            if (!empty($inform_info['inform_pic3'])) {
                $pics[] = $inform_info['inform_pic3'];
            }
            $this->drop_inform($inform_id, $pics);
            ds_json_encode(10000,lang('inform_cancel_success'));
        } else {
            ds_json_encode(10001,lang('inform_cancel_fail'));
        }
    }

    /**
     * 商品举报详细
     */
    public function inform_info() {

        $inform_id = intval(input('param.inform_id'));
        $inform_info = $this->get_inform_info($inform_id);
        View::assign('inform_info', $inform_info);
        // 商品信息
        $goods_info = model('goods')->getGoodsInfoByID($inform_info['inform_goods_id']);
        View::assign('goods_info', $goods_info);
        // 投诉类型
        $subject_info = model('informsubject')->getOneInformsubject(array('informsubject_id' => $inform_info['informsubject_id']));
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_inform');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('inform_list');
        View::assign('subject_info', $subject_info);
        return View::fetch($this->template_dir . 'inform_info');
    }

    /*
     * 根据id获取投诉详细信息
     */

    private function get_inform_info($inform_id) {

        if (empty($inform_id)) {
            $this->error(lang('param_error'));
        }

        $inform_model = model('inform');
        $inform_info = $inform_model->getOneInform(array('inform_id'=>$inform_id));

        if (empty($inform_info)) {
            $this->error(lang('param_error'));
        }

        if (intval($inform_info['inform_member_id']) !== intval(session('member_id'))) {
            $this->error(lang('param_error'));
        }

        return $inform_info;
    }

    /*
     * 根据id获取投诉详细信息
     */

    private function drop_inform($inform_id, $inform_pics) {

        $inform_model = model('inform');
        //删除图片
        if (!empty($inform_pics)) {
            foreach ($inform_pics as $pic) {
                $this->inform_delete_pic($pic);
            }
        }
        $inform_model->delInform(array('inform_id' => $inform_id));
    }

    /*
     * 根据id获取商品详细信息
     */

    private function get_goods_info_byid($goods_id) {

        if (empty($goods_id)) {
            $this->error(lang('param_error'));
        }

        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsOnlineInfoByID($goods_id);

        //检查该商品是否存在
        if (empty($goods_info)) {
            $this->error(lang('goods_null'));
        }

        return $goods_info;
    }

    /*
     * 检查当前用户是否允许举报
     */

    private function check_member_allow_inform() {

        //检查是否允许举报
        $member_model = model('member');
        if (!$member_model->isMemberAllowInform(session('member_id'))) {
            $this->error(lang('deny_inform'));
        }
    }

    /*
     * 上传用户提供的举报图片
     */

    private function inform_upload_pic($inform_pic) {
        
        $pic_name = array();
        $count = 1;
        foreach ($inform_pic as $pic) {
            if (!empty($_FILES[$pic]['name'])) {
                $file_name = session('member_id') . '_' . date('YmdHis') . rand(10000, 99999).'.png';
                $res = ds_upload_pic('home'.DIRECTORY_SEPARATOR.'inform', $pic, $file_name);
                if ($res['code']) {
                    $pic_name[$count] = $res['data']['file_name'];
                } else {
                    $pic_name[$count] = '';
                }
            }else{
                $pic_name[$count] = '';
            }
            $count++;
        }
        return $pic_name;
    }

    /*
     * 上传用户提供的举报图片
     */

    private function inform_delete_pic($pic_name) {

        //上传路径
        $pic = BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_PATH . DIRECTORY_SEPARATOR . 'inform' . DIRECTORY_SEPARATOR . $pic_name;
        if (file_exists($pic)) {
            @unlink($pic);
        }
    }


    /*
     * 根据举报类型id获取，举报具体列表
     */

    public function get_subject_by_typeid() {
        $informsubject_type_id = intval(input('param.type_id'));

        if (empty($informsubject_type_id)) {
            echo '';
        } else {
            /*
             * 获得举报主题列表
             */
            $informsubject_model = model('informsubject');

            //搜索条件
            $condition = array();
            $condition[] = array('informsubject_type_id','=',$informsubject_type_id);
            $condition[] = array('informsubject_state','=',1);
            $inform_subject_list = $informsubject_model->getInformsubjectList($condition, 10, 'informsubject_id,informsubject_content');

            echo json_encode($inform_subject_list);
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @param array $array 附加菜单
     *
     * @return
     */
    public function getMemberItemList() {
        $menu_array = array(
            array(
                'name' => 'inform_list',
                'text' => lang('violation_report'),
                'url' => (string)url('Memberinform/index')
            ),
        );

        return $menu_array;
    }

}
