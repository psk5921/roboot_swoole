<?php
namespace app\api\model;

use app\api\consts\HttpCode;
use think\exception\PDOException;
use think\Model;

class Content extends Model
{
    protected $insert = ['create_at'];  //新增数据字段自动完成
    /**
     * 插入自动填充create_at信息
     * @return string
     */
    protected function setCreateAtAttr()
    {
        return time();
    }
    /**
     * 创建用户数据资源
     * @param $data  array
     * @return \think\response\Json
     */
    public function createContent($data)
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
                $res['data'] = $data['content'];
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

    //获取内容
    public function getContent($where){
         if(empty($where)){
              return false;
         }
         $select = $this->where($where)->order('create_at asc')->field('content,create_at')->select();
         return $select;
    }
}
