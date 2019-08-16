<?php

namespace app\api\controller;

use think\Controller;
use app\api\model\User as UserModel;
use app\api\model\Content;
use app\api\consts\HttpCode;

class  User extends Controller
{
    private $protocol = 'http';
    private $json = [
        'code' => 0,
        'msg' => '',
        'data' => '',
    ];  //返回数据格式

    /**
     * 创建用户  返回调用的url
     */
    public function createUser()
    {
        if (request()->isPost()) {
            $roboot_name = input('roboot_name'); //输入的机器人名称
            if (!isset($roboot_name)) {
                $this->json['msg'] = 'Miss Parameters';
                $this->json['code'] = -1;
                return json($this->json, HttpCode::HTTP_CODE_FOR_202[0]);
            }
            if (empty($roboot_name)) {
                $this->json['msg'] = 'Parameters Empty';
                $this->json['code'] = -1;
                return json($this->json, HttpCode::HTTP_CODE_FOR_202[0]);
            }
            $UserModel = new UserModel;
            if ($UserModel->uniqueByUser($roboot_name)) {
                $this->json['msg'] = 'This Name Have Been Created';
                $this->json['code'] = -1;
                return json($this->json, HttpCode::HTTP_CODE_FOR_202[0]);
            }
            $access_token = $this->createAccessToken(50);
            $insert = [
                'roboot_name' => htmlspecialchars($roboot_name),
                'access_token' => $access_token,
                'url' => $this->protocol . '://' . request()->server()['HTTP_HOST'] . '/api/content/send?access_token=' . $access_token,
            ];
            $res = $UserModel->createUser($insert);
            return $res;
        }
    }


    /**
     * 查找机器人名称是否存在
     */
    public function search(){
       if(request()->isPost()){
           $name = htmlspecialchars(input('name'));
           $UserModel = new UserModel;
           if($id=$UserModel->uniqueByUser($name,false)) {
               $where = ['uid'=>$id];
               $Content = new Content;
               $data = $Content->getContent($where);
               $arr = [];
               if(count($data)>1){
                 foreach ($data as $k=>$items){
                     $arr[$k]['content'] =  $items['content'];
                     $arr[$k]['create_at'] =   date('H:i',$items['create_at']);
                     $arr[$k]['time'] =   $items['create_at'];
                 }
                   $res['data'] = ['name'=>$name,'list'=>$arr,'time'=>$arr[count($arr)-1]['time']];
               }else{
                   $res['data'] = ['name'=>$name,'list'=>[],'time'=>0];
               }
               $res['msg'] = 'Success';
               $res['code'] = 1;

               unset($data);
               return json($res, HttpCode::HTTP_CODE_FOR_200[0]);
           }else{
               $res['msg'] = 'Can\'t serach info';
               $res['code'] = -1;
               $res['data'] = $name;
               return json($res, HttpCode::HTTP_CODE_FOR_202[0]);
           }
       }
    }
    /**
     * 生成不重复的accessToken
     */
    private function createAccessToken($length = 36)
    {
        $str = 'abcdefg0123456789';
        $access_token = '';
        if ($length == 0) {
            return;
        }
        for ($i = 0; $i < $length; $i++) {
            $access_token .= $str[mt_rand(0, strlen($str) - 1)];
        }
        $UserModel = new UserModel;
        $res = $UserModel->getUserByAccessToken($access_token);
        if ($res) {
            $access_token = $this->createAccessToken($length);
        }
        return $access_token;
    }
}
