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
class Membermallconsult extends BaseMember {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/membermallconsult.lang.php');
    }

    /**
     * 平台客服咨询首页
     */
    public function index() {
        // 咨询列表
        $mallconsult_model = model('mallconsult');
        $consult_list = $mallconsult_model->getMallconsultList(array('member_id' => session('member_id')), '*', '10');
        View::assign('consult_list', $consult_list);
        View::assign('show_page', $mallconsult_model->page_info->render());

        // 回复状态
        $this->typeState();
        $this->setMemberCurMenu('member_mallconsult');
        $this->setMemberCurItem('consult_list');
        return View::fetch($this->template_dir . 'member_mallconsult_list');
    }

    /**
     * 平台咨询详细
     */
    public function mallconsult_info() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('param_error'));
        }
        // 咨询详细信息
        $consult_info = model('mallconsult')->getMallconsultInfo(array(
            'mallconsult_id' => $id, 'member_id' => session('member_id')
        ));
        View::assign('consult_info', $consult_info);

        // 咨询类型列表
        $type_list = model('mallconsulttype')->getMallconsulttypeList(array(), 'mallconsulttype_id,mallconsulttype_name', 'mallconsulttype_id');
        View::assign('type_list', $type_list);

        // 回复状态
        $this->typeState();

        $this->setMemberCurMenu('member_mallconsult');
        $this->setMemberCurItem('consult_list');
        return View::fetch($this->template_dir . 'member_mallconsult_info');
    }

    /**
     * 添加平台客服咨询
     */
    public function add_mallconsult() {
        // 咨询类型列表
        $type_list = model('mallconsulttype')->getMallconsulttypeList(array());
        View::assign('type_list', $type_list);
        if (input('param.inajax')) {
            return View::fetch($this->template_dir . 'add_inajax');
        } else {
            $this->setMemberCurMenu('member_mallconsult');
            $this->setMemberCurItem('consult_list');
            return View::fetch($this->template_dir . 'member_mallconsult_add');
        }
    }

    /**
     * 保存平台咨询
     */
    public function save_mallconsult() {
        if (!request()->isPost()) {
            ds_json_encode(10001,lang('param_error'));
        }

        //验证表单信息
        $data = [
            'type_id' => input('post.type_id'),
            'consult_content' => input('post.consult_content')
        ];

        $mallconsult_validate = ds_validate('mallconsult');
        if (!$mallconsult_validate->scene('save_mallconsult')->check($data)) {
            ds_json_encode(10001,$mallconsult_validate->getError());
        }

        $insert = array();
        $insert['mallconsulttype_id'] = input('post.type_id');
        $insert['member_id'] = session('member_id');
        $insert['member_name'] = session('member_name');
        $insert['mallconsult_content'] = input('post.consult_content');
        
        $res=word_filter($insert['mallconsult_content']);
        if(!$res['code']){
            ds_json_encode(10001,$res['msg']);
        }
        $insert['mallconsult_content']=$res['data']['text'];

        $result = model('mallconsult')->addMallconsult($insert);
        if ($result) {
            ds_json_encode(10000,lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_op_fail'));
        }
    }

    /**
     * 删除平台客服咨询
     */
    public function del_mallconsult() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            ds_json_encode(10001,lang('param_error'));
        }

        $result = model('mallconsult')->delMallconsult(array('mallconsult_id' => $id, 'member_id' => session('member_id')));
        if ($result) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }

    /**
     * 咨询的回复状态
     */
    private function typeState() {
        $state = array('0' => lang('did_not_return'), '1' => lang('have_to_reply'));
        View::assign('state', $state);
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getMemberItemList() {
        $menu_array = array(
            array(
                'name' => 'consult_list', 'text' => lang('platform_customer_service_consultation_list'),
                'url' => (string)url('Membermallconsult/index')
            ),
        );
        return $menu_array;
    }

}