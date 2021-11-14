<?php

namespace app\admin\controller;
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
class Mallconsult extends AdminControl {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/mallconsult.lang.php');
    }

    /**
     * 咨询管理
     */
    public function index() {
        $condition = array();
        $member_name = trim(input('param.member_name'));
        if ($member_name != '') {
            $condition[]=array('member_name','like', '%' . $member_name . '%');
            View::assign('member_name', $member_name);
        }
        $mallconsulttype_id = intval(input('param.mctid'));
        if ($mallconsulttype_id > 0) {
            $condition[]=array('mallconsulttype_id','=',$mallconsulttype_id);
            View::assign('mctid', $mallconsulttype_id);
        }
        $mallconsult_model = model('mallconsult');
        $consult_list = $mallconsult_model->getMallconsultList($condition, '*', 10);
        View::assign('show_page', $mallconsult_model->page_info->render());
        View::assign('consult_list', $consult_list);


        // 咨询类型列表
        $type_list = model('mallconsulttype')->getMallconsulttypeList(array(), 'mallconsulttype_id,mallconsulttype_name', 'mallconsulttype_id');
        View::assign('type_list', $type_list);

        // 回复状态
        $state = array('0' => lang('state_0'), '1' => lang('state_1'));
        View::assign('state', $state);
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /**
     * 回复咨询
     */
    public function consult_reply() {
        $mallconsult_model = model('mallconsult');
        if (request()->isPost()) {
            $mallconsult_id = intval(input('post.mallconsult_id'));
            $reply_content = trim(input('post.reply_content'));
            if ($mallconsult_id <= 0 || $reply_content == '') {
                $this->error(lang('param_error'));
            }
            $update['mallconsult_isreply'] = 1;
            $update['mallconsult_reply_content'] = $reply_content;
            $update['mallconsult_replytime'] = TIMESTAMP;
            $update['admin_id'] = $this->admin_info['admin_id'];
            $update['admin_name'] = $this->admin_info['admin_name'];
            $result = $mallconsult_model->editMallconsult(array('mallconsult_id' => $mallconsult_id), $update);
            if ($result) {
                $consult_info = $mallconsult_model->getMallconsultInfo(array('mallconsult_id' => $mallconsult_id));
                // 发送用户消息
                $param = array();
                $param['code'] = 'consult_mall_reply';
                $param['member_id'] = $consult_info['member_id'];
                //阿里短信参数
                $param['ali_param'] = array();
                $param['ten_param'] = array();
                $param['param'] = array(
                    'consult_url' => HOME_SITE_URL .'/Membermallconsult/mallconsult_info?id='.$mallconsult_id
                );
                //微信模板消息
                $param['weixin_param'] = array(
//                    'url' => config('ds_config.h5_site_url').'/member/consult_list',
                    'data'=>array(
                        "keyword1" => array(
                            "value" => date('Y-m-d', $consult_info['mallconsult_addtime']),
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $consult_info['mallconsult_content'],
                            "color" => "#333"
                        ),
                        "keyword3" => array(
                            "value" => $consult_info['mallconsult_reply_content'],
                            "color" => "#333"
                        )
                    ),
                );
                model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendMemberMsg','cron_value'=>serialize($param)));

                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        } else {
            $id = intval(input('param.id'));
            if ($id <= 0) {
                $this->error(lang('param_error'));
            }

            $consult_info = $mallconsult_model->getMallconsultDetail($id);
            View::assign('consult_info', $consult_info);
            return View::fetch();
        }
    }

    /**
     * 删除平台客服咨询
     */
    public function del_consult() {
        $mallconsult_id = input('param.mallconsult_id');
        $mallconsult_id_array = ds_delete_param($mallconsult_id);
        if ($mallconsult_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $condition = array();
        $condition[]=array('mallconsult_id','in',$mallconsult_id_array);
        
        $result = model('mallconsult')->delMallconsult($condition);
        if ($result) {
            $this->log('删除平台客服咨询' . '[ID:' . $mallconsult_id . ']');
            ds_json_encode('10000', lang('ds_common_del_succ'));
        } else {
            ds_json_encode('10001', lang('ds_common_del_fail'));
        }
    }


    /**
     * 咨询类型列表
     */
    public function type_list() {
        $mallconsulttype_model = model('mallconsulttype');
        $type_list = $mallconsulttype_model->getMallconsulttypeList(array(), 'mallconsulttype_id,mallconsulttype_name,mallconsulttype_sort');
        View::assign('type_list', $type_list);
        $this->setAdminCurItem('type_list');
        return View::fetch();
    }

    /**
     * 新增咨询类型
     */
    public function type_add() {
        if (request()->isPost()) {
            // 验证
            $data = [
                'mallconsulttype_name' => input('post.mallconsulttype_name'),
                'mallconsulttype_sort' => input('post.mallconsulttype_sort')
            ];
            $mallconsult_validate = ds_validate('mallconsult');
            if (!$mallconsult_validate->scene('type_add')->check($data)) {
                $this->error(lang('ds_common_op_fail') . $mallconsult_validate->getError());
            }

            $insert = array();
            $insert['mallconsulttype_name'] = trim(input('post.mallconsulttype_name'));
            $insert['mallconsulttype_introduce'] = input('post.mallconsulttype_introduce');
            $insert['mallconsulttype_sort'] = intval(input('post.mallconsulttype_sort'));
            $result = model('mallconsulttype')->addMallconsulttype($insert);
            if ($result) {
                $this->log('新增咨询类型', 1);
                dsLayerOpenSuccess(lang('ds_common_save_succ'));
            } else {
                $this->log('新增咨询类型', 0);
                $this->error(lang('ds_common_save_fail'));
            }
        }
        return View::fetch();
    }

    /**
     * 编辑咨询类型
     */
    public function type_edit() {
        $mallconsulttype_id = intval(input('param.mallconsulttype_id'));
        if ($mallconsulttype_id <= 0) {
            $this->error(lang('param_error'));
        }
        $mallconsulttype_model = model('mallconsulttype');
        if (request()->isPost()) {
            // 验证
            $data = [
                'mallconsulttype_name' => input('post.mallconsulttype_name'),
                'mallconsulttype_sort' => input('post.mallconsulttype_sort')
            ];
            $mallconsult_validate = ds_validate('mallconsult');
            if (!$mallconsult_validate->scene('type_edit')->check($data)) {
                $this->error(lang('ds_common_op_fail') . $mallconsult_validate->getError());
            }
            $condition = array();
            $condition[] = array('mallconsulttype_id','=',$mallconsulttype_id);
            $update = array();
            $update['mallconsulttype_name'] = trim(input('post.mallconsulttype_name'));
            $update['mallconsulttype_introduce'] = input('post.mallconsulttype_introduce');
            $update['mallconsulttype_sort'] = intval(input('post.mallconsulttype_sort'));
            $result = $mallconsulttype_model->editMallconsulttype($condition, $update);
            if ($result>=0) {
                $this->log('编辑平台客服咨询类型 ID:' . $mallconsulttype_id, 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->log('编辑平台客服咨询类型 ID:' . $mallconsulttype_id, 0);
                $this->error(lang('ds_common_op_fail'));
            }
        } else {
            $mallconsulttype_info = $mallconsulttype_model->getMallconsulttypeInfo(array('mallconsulttype_id' => $mallconsulttype_id));
            View::assign('mallconsulttype_info', $mallconsulttype_info);
            return View::fetch();
        }
    }

    /**
     * 删除咨询类型
     */
    public function type_del() {
        $mallconsulttype_id = input('param.mallconsulttype_id');
        $mallconsulttype_id_array = ds_delete_param($mallconsulttype_id);
        if ($mallconsulttype_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $condition = array();
        $condition[]=array('mallconsulttype_id','in',$mallconsulttype_id_array);
        $result = model('mallconsulttype')->delMallconsulttype($condition);
        if ($result) {
            $this->log('删除平台客服咨询类型 ID:' . $mallconsulttype_id, 1);
            ds_json_encode('10000', lang('ds_common_del_succ'));
        } else {
            $this->log('删除平台客服咨询类型 ID:' . $mallconsulttype_id, 0);
            ds_json_encode('10001', lang('ds_common_del_fail'));
        }
    }

    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => lang('mallconsult_index'), 'url' => (string)url('Mallconsult/index')
            ), array(
                'name' => 'type_list', 'text' => lang('mallconsult_type_list'), 'url' => (string)url('Mallconsult/type_list')
            ), array(
                'name' => 'type_add', 'text' => lang('mallconsult_type_add'), 'url' =>"javascript:dsLayerOpen('".(string)url('Mallconsult/type_add')."','".lang('mallconsult_type_add')."')"
            ),
        );
        if (request()->action() == 'type_edit')
            $menu_array[] = array(
                'name' => 'type_edit', 'text' => lang('mallconsult_type_edit'), 'url' => ''
            );
        return $menu_array;
    }

}