<?php
# 获取 DB 处理函数名称:
// domain_meta_query()->域名查询并处理
// traffic_meta_query()->流量查询并处理
// interface_meta_query()->网络接口查询并处理
// getServiceStatus()->Spectre 状态查询并处理

# 封装接口 API 以及返回格式:
/*
/setting/getProfile
-->说明:服务(ss-tunnel||ss-redir)重新启动接口
-->请求方式: get
-->post 请求参数: restartService=重新启动的服务名称
-->返回格式: json(对象数组)

[{id: "1",
profile_name: "config-no-1",
server_address: "",
server_port: "",
password: "",
local_port: "",
timeout: "",
method: "",
auth: "0"}]

/setting/createProfile
-->说明:新建 profile
-->请求方式: post
-->post 请求参数: json
-->返回格式: json(新创建的对象数组)
新建成功:
{
"profile_name": "新创建的 profile 名",
"server_address": "",
"server_port": "",
"password": "",
"local_port": "",
"timeout": "",
"method": "",
"auth": "0"
}


/setting/updateProfile
-->说明:更新 profile
-->请求方式: post
-->post 请求参数: json
-->返回格式: json(修改后的对象数组)
更新成功(返回更新后的配置):
{
"profile_name": "",
"server_address": "",
"server_port": "",
"password": "",
"local_port": "",
"timeout": "",
"method": "",
"auth": "0"
}
更新失败:
{
"updateProfile": "con9",
"status": 0
}

/setting/deleteProfile
-->说明:删除 profile 接口
-->请求方式: post
-->post 请求参数
profileName=将要删除的 profile 名字
truncate=boolean 是否要重置整个配置
-->返回格式:json()
删除成功:
{
"deleteProfile"="将要删除的 profile 名字",
"truncate" = boolean,
"status" = 1
}
删除失败:
{
"deleteProfile"="将要删除的 profile 名字",
"truncate" = boolean,
"status" = 1
}

/setting/activateProfile
-->说明:启动 profile 接口
-->请求方式: post
-->post 请求参数
profileName=将要启动的 profile 名字
-->返回格式:json()
启动成功:
{
"activeProfile"="已经启动的 profile 名字",
"status" = 1
}
失败启动:
{
"activeProfile"="将要启动的 profile 名字",
"status" = 0,
"message" = "错误信息"
}

*/


class Setting extends CI_Controller {
    
    /************************ utils function ********************************************/
    
    //处理 PUT 请求
    public function resolvePut(){
        if ($this->input->method() == "put") {
            // $jsonArray = $this->input->input_stream();
            // print_r ($jsonArray);
            // return $jsonArray;
        }
        else{
            return 0;
        }
    }
    
    
    //处理 Delete 请求
    public function resolveDel(){
        if ($this->input->method() == "delete") {
            print_r ($this->input->raw_input_stream);
        }
        else{
            return 0;
        }
    }
    
    
    /**
    * 解析 HTTP post 以及 get 请求
    *
    * @return array                   对应请求的参数数组,同时包含请求 IP
    *
    */
    public function resolveRequest(){
        $ip=array('ip' => $this->input->ip_address());
        if ($this->input->method() == "get") {
            $params = $this->input->get(NULL, TRUE);
        }
        elseif ($this->input->method() == "post" && $this->input->get_request_header('Content-Type', TRUE) == "application/json") {
            $params = $this->security->xss_clean($this->input->raw_input_stream);
            return json_decode($params,true);
        }
        elseif ($this->input->method() == "post") {
            $params = $this->input->post(NULL, TRUE);
            return array_merge_recursive($params,$ip);
        }
        else{
            return 0;
        }
        
    }
    
    /**
    * 更新 DB 中的配置文件
    * @param  array       $data       将要更新的配置文件
    * @return array                   成功更新后的数组
    *
    */
    public function updateDB($data){
        if(isset($data) && $this->searchDB("profile_name",$data['profile_name'], "socks_config")){
            $this->db->replace('socks_config', $data);
            return $data;
        }
        else {
            return array(
            'updateProfile' =>$data['profile_name'],
            'status'=>0 );
        }
    }
    
    /**
    * 插入配置文件至 DB
    * @param  array       $data       将要插入的配置文件
    * @return array                   成功插入后的数组
    *
    */
    public function insertDB($data){
        if (isset($data['profile_name']) && isset($data['server_address'])) {
            $insertData= array(
            'profile_name' => $data['profile_name'],
            'server_address' => $data['server_address'],
            'server_port' => $data['server_port'],
            'password' => $data['password'],
            'local_port' => $data['local_port'],
            'timeout' => $data['timeout'],
            'method' => $data['method'],
            'auth' => $data['auth']
            );
            $this->db->insert('socks_config', $insertData);
            return $data;
        }
        
    }
    
    /**
    * 数据库查询
    *
    * @param string        $params     表名
    * @return array                    查找结果0
    *
    */
    public function queryDB($params){
        if (isset($params)) {
            $objectArray = [];
            $query = $this->db->get($params);
            foreach ($query->result() as $row)
            {
                array_push($objectArray,$row);
            }
            return $objectArray;
        }
        else{
            echo "need params";
        }
        
    }
    
    /**
    * 查找数据库表的键值
    *
    * @param string        $prop       数据表中存在的字段
    * @param string        $params     传入的键值
    * @param string        $table      表名
    * @return array                   查找结果
    *
    */
    public function searchDB($prop, $params, $table){
        $this->db->select('*');
        $this->db->from($table);
        $this->db->where($prop, $params);
        $query = $this->db->get();
        $objectArray = [];
        foreach ($query->result_array() as $row)
        {
            array_push($objectArray,$row);
        }
        return $objectArray;
    }
    
    /**
    * 删除配置文件
    *
    * @param  array        $params     即将删除的配置文件
    * @return array                    成功则返回删除文件信息,失败则返回失败信息
    *
    */
    public function deleteDB($params){
        if(isset($params['profileName']) && $params['truncate'] == "false"){
            if (count($this->searchDB("profile_name",$params['profileName'], "socks_config"))){
                $this->db->delete('socks_config', array('profile_name' => $params['profileName']));
                return array('deleteProfile'=> $params['profileName'] , 'truncate' => false, 'status' => 1);
            }
            else {
                return array(
                'deleteProfile' =>$params['profileName'],
                'truncate'=>false,
                'status'=>0 );
                
            }
            
        }
        else if(isset($params['truncate']) && $params['truncate'] == "true"){
            $this->db->truncate('socks_config');
            return array('truncate' => true, 'status' => 1);
        }
    }
    
    /**
    * 封装并输出 Json
    *
    * @param array         $data       将要输出的数据
    * @return
    *
    */
    public function jsonOutput($data){
        $this->output
        ->set_status_header(200)
        ->set_header('Access-Control-Allow-Origin: *')
        ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
        ->set_header('Pragma: no-cache')
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ->_display();
        exit;
    }
    
    /************************ Process activate ****************************************/
    //判断请求是否合理 done
    //合理则关闭正在运行的配置文件 done
    //检查正在运行的文件配置将 active 置为 0,同时将新的配置 active 置为 1,
    //输出配置 json 到 swap
    //从 swap 中启动新的配置文件
    //检查新的配置是否运行
    //返回回调
    public function activateProcess($params){
        if (isset($params['profileName']) && count($this->searchDB("profile_name",$params['profileName'], "socks_config"))){
            $activeProfile = $this->searchDB("active", "1", "socks_config");
            if ($activeProfile[0]['profile_name'] == $params['profileName']) {
                $this->killProcess($activeProfile[0]['profile_name']);
                #TODO
            }
            
            
        }else{
            return array(
            'activeProfile' => $params['profileName'],
            'status' => 0,
            'message' => '配置文件不存在或者参数为空');
        }
    }
    
    /**
    * 检查进程是否运行
    *
    * @param string        $processName       进程名
    * @param string        $profileName       进程使用的配置文件名无需加扩展名json
    * @return array(
    *               'status' => 0或1,
    *               'processName'=>$processName,
    *               'profileName'=>$profileName);
    *
    */
    public function checkProcess($processName,$profileName){
        $this->load->library('command');
        $getprocessInfo = new Command('ps');
        $getprocessInfo -> setArgs('-aux|grep ' . $processName);
        if ($getprocessInfo->execute()){
            $getprocessInfo->getOutput();
            $status = substr_count($getprocessInfo->getOutput(), $processName) > 2 ? 1: 0;
            if (substr_count($getprocessInfo->getOutput(), $profileName . ".json")!==0){
                print_r(array('status' => $status,'processName'=>$processName,'profileName'=>$profileName));
                return array('status' => $status,'processName'=>$processName,'profileName'=>$profileName);
            };
        }
        else {
            return array(
            'status' => 0,
            'processName'=>$processName,
            'profileName'=>$profileName);
        }
    }
    
    /**
    * 杀死进程
    *
    * @param string        $processName       进程名
    * @return array(
    *               'status' => 0或1,
    *               'processName'=>$processName);
    *
    */
    public function killProcess($processName){
        $this->load->library('command');
        $killProcess = new Command('sudo kill');
        $killProcess -> setArgs("-9 `ps -aux|grep " . $processName . "|awk '/\.json/{print $2}'`");
        if ($killProcess->execute()){
            echo $killProcess->getOutput();
            
        }
        else {
            //command 的 bug $killProcess 已经执行
            echo $killProcess;
            return array(
            'status' => 1,
            'processName'=>$processName);
        }
    }
    
    public function test(){
        $data = $this->resolveRequest();
        $this->activateProcess($data);
    }
    
    // public function test(){
    //     $this->killProcess("ss-redir");
    // }
    
    
    /**
    * 写入 Json 文件
    *
    * @param string        $path       写入的路径
    * @param string        $fileName   写入的文件名,不存在则自动创建,无需加文件类型,
    * @param array         $data       写入的数据
    * @return
    *
    */
    public function writeToFile($path,$fileName,$data){
        $this->load->helper('file');
        write_file($path . $fileName . ".json", json_encode($data));
        print_r("writeToFile success");
    }
    
    
    
    /************************ API function ********************************************/
    
    //查询 profile 接口
    public function getProfile(){
        $objectArray = $this->queryDB('socks_config');
        $this->jsonOutput($objectArray);
    }
    //创建 profile 接口
    public function createProfile(){
        $data = $this->resolveRequest();
        $objectArray = $this->insertDB($data);
        $this->jsonOutput($objectArray);
    }
    //更新 profile 接口
    public function updateProfile(){
        $data = $this->resolveRequest();
        $objectArray = $this->updateDB($data);
        $this->jsonOutput($objectArray);
        
    }
    //删除 profile 接口
    public function deleteProfile(){
        $data = $this->resolveRequest();
        $objectArray = $this->deleteDB($data);
        $this->jsonOutput($objectArray);
        
    }
    //激活 profile 接口
    public function activateProfile(){
        $data = $this->resolveRequest();
        $this->activateProcess($data);
        // $this->jsonOutput($objectArray);
        // print_r($objectArray);
    }
    //视图区域
    public function index(){
        $this->load->view('setting_view');
    }
    
}