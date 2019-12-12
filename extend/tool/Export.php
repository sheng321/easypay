<?php

namespace tool;

class Export{
	/**
	 * params $headerList 头部列表信息(一维数组) 必传
	 * params $data 导出的数据(二维数组)	必传
	 * params $filename 文件名称转码 必传
	 * params $tmp 备用信息(二维数组) 选传
	 * PS:出现数字格式化情况，可添加看不见的符号，使其正常，如:"\t"
	 **/
	public static  function exportToCsv($headerList = [] , $data = [] , $fileName = '' , $tmp = []){

        set_time_limit(0);
		//文件名称转码
		$fileName = iconv('UTF-8', 'GBK', $fileName);
		//设置header头
		header('Content-Type: application/vnd.ms-excel;charset=utf-8');
		header('Content-Disposition: attachment;filename=' . $fileName . '.csv');
        header('Cache-Control: max-age=0');

        header("Content-Type:application/octet-stream");
        header("Content-Type:application/force-download");
        header("Content-Type:application/download");;
        header('Expires:0');
        header('Pragma:public');

        //打开PHP文件句柄,php://output,表示直接输出到浏览器
        $fp = fopen("php://output","a");

        //输出Excel列表名称信息
        foreach ($headerList as $key => $value) {
        	$headerList[$key] = iconv('UTF-8', 'GBK', $value);//CSV的EXCEL支持BGK编码，一定要转换，否则乱码
        }
        //使用fputcsv将数据写入文件句柄
        fputcsv($fp, $headerList);
        //计数器
        $num = 0;
        //每隔$limit行，刷新一下输出buffer,不要太大亦不要太小
        $limit = 50000;
        //逐行去除数据,不浪费内存
        $count = count($data);
        for($i = 0 ; $i < $count ; $i++){
        	$num++;
        	//刷新一下输出buffer，防止由于数据过多造成问题
        	if($limit == $num){
        		ob_flush();
        		flush();
        		$num = 0;
        	}
        	$row = $data[$i];
        	foreach ($row as $key => $value) {
        		$row[$key] = iconv('UTF-8', 'GBK', $value);
        	}
        	fputcsv($fp, $row);
        }

        //备用信息
        foreach ($tmp as $key => $value) {
            $tmp[$key] = iconv("UTF-8", 'GBK', $value);
        }
        if(empty($tmp)){
            //使用fputcsv将数据写入文件句柄
            fputcsv($fp, $tmp);
        }
	}
}