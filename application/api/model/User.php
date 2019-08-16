<?php

namespace app\api\model;

use think\exception\PDOException;
use think\Model;
use app\api\consts\HttpCode;

class User extends Model
{
    protected $insert = ['ip', 'create_at'];  //新增数据字段自动完成

    /**
     * 插入自动填充ip信息
     * @return string
     */
    protected function setIpAttr()
    {
        return request()->ip();
    }


    /**
     * 插入自动填充create_at信息
     * @return string
     */
    protected function setCreateAtAttr()
    {
        return time();
    }


    /**
     * 通过accessToken获取id
     * @param $accessToken  string
     * @return bool|mixed
     */
    public function getUserByAccessToken($accessToken)
    {
        if (empty($accessToken) || !is_string($accessToken)) {
            return false;
        }
        $where = [['access_token' ,'=',$accessToken]];
        $result = $this->where($where)->value('id');
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 过滤同一ip下的机器人信息重复生成
     * @param $roboot_name
     * @return bool|mixed
     */
    public function uniqueByUser($roboot_name,$ip=true)
    {
        if (empty($roboot_name)) {
            return false;
        }
        $where = ['roboot_name' => $roboot_name];
        $result = $this->where($where)->value('id');
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 创建用户数据资源
     * @param $data  array
     * @return \think\response\Json
     */
    public function createUser($data)
    {
        $res = [
            'code' => 0,
            'msg' => '',
            'data' => '',
        ];
        try {
            if (empty($data) || !is_array($data)) {
                throw  new PDOException('参数有误');
            }
            $result = $this->allowField(true)->save($data);
            if ($result) {
                $res['msg'] = 'Success';
                $res['code'] = 1;
                $res['data'] = $data['url'];
                return json($res, HttpCode::HTTP_CODE_FOR_201[0]);
            } else {
                $res['msg'] = 'Create Fail';
                $res['code'] = 1;
                return json($res, HttpCode::HTTP_CODE_FOR_202[0]);
            }
        } catch (PDOException $e) {
            $res['msg'] = $e->getMessage();
            $res['code'] = -1;
            return json($res, HttpCode::HTTP_CODE_FOR_202[0]);
        }
    }
}
