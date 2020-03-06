{__NOLAYOUT__}
<!DOCTYPE html>
<html lang="zh-CN"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1">
    <meta name="description" content="">
    <meta name="Keywords" content="">
    <meta name="renderer" content="webkit">
    <meta name="force-rendering" content="webkit">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no">
    <link rel="Bookmark" href="/favicon.ico">
    <link rel="Shortcut Icon" href="/favicon.ico">
    <link rel="stylesheet" type="text/css" href="./JuFu_files/pay.css">
    <title>便民服务</title>
    <style>
        .payfail img{
            width: auto!important;
            height: auto;
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body style="background:white">
<div class="payfail"  >
    <img src="__COMMON__/images/payfail.png">
    <p>{$msg}</p>
    <a href="javascript:toClose();">关闭</a>
</div>

<style>
    /*basicset*/
    html,body,div,span,p,a,img,ul,li,h1,h2,h3,h4,h5,h6,input{
        margin: 0;
        padding: 0;
        border: none;
        font-family: "微软雅黑";
        font-size: 0.16rem;
    }
    ul,li{list-style: none;}
    a{text-decoration: none;}
    html{font-size: 100px;}
    .clear{
        clear: both;
    }
    .clearfix:after{ content:""; display:block; height:0px; clear:both; visibility:hidden;}

    /*Iphone5*/
    @media only screen and (min-width: 320px){
        html{font-size: 85px;}
    }
    @media only screen and (min-width: 320px)and (max-height:480px){
        html{font-size: 85px;}
    }
    /*Iphone6*/
    @media only screen and (min-width: 375px){
        html{font-size: 100px;}
    }
    @media only screen and (min-width: 375px){
        html{font-size: 100px;}

    }
    @media only screen and (min-width: 375px) and (max-height:600px){
        html{font-size: 100px;}
    }
    /*Iphone6Plus*/
    @media only screen and (min-width: 414px){
        html{font-size: 110px;}
    }

    @media only screen and (min-width: 1000px) and (max-width: 2000px) {
        .payinfo{
            width: 80%;
            margin: auto!important;
        }
        .ewm{
            width: 250px!important;
        }
        .payfail{
            margin: 10% auto 0!important;
            width:50%!important;
        }
        .payfail img{
            display: block;
            width: 50%!important;
            margin: auto;
        }
        .creat{
            margin-top: 25%!important;
        }
        .paysucc{
            width: 50%!important;
            margin: 5% auto 0;
        }
        .payfail p{
            font-size: 24px!important;
            color: #333333;
            text-align: center;
            margin: 0.2rem 0;
        }
        .tc{
            margin-top: 0%;
            max-width: 70%;
            border-radius: 8px;
            overflow: hidden;
        }
        .btns {
            border:none!important;
        }


    }

    .paybox{
        padding-top: 10%;
        padding-bottom: 10%;
    }
    .paynum{
        text-align: center;
    }
    .paynum h1{
        font-size: 14px;
        color: #ff4d4d;
        text-align: center;
    }
    .paynum p{
        font-size: 14px;
        color: #333;
        text-align: center;
        margin: 0.15rem 0;
    }
    .paynum h2{
        font-size: 30px;
        color: #ff6600;
        text-align: center;
    }
    .ewm{
        width: 250px;
        height: 250px;
        margin:0.2rem auto;
        border: 1px solid #ededed;
        border-radius: 5px;
    }
    .ewm img{
        display: block;
        width: 100%;
    }
    .orderinfo{
        text-align: center;
    }
    .orderinfo h1,.orderinfo span,.orderinfo h2{
        font-size: 14px;
        color: #424242;
        font-weight: normal;
        line-height: 0.24rem;
    }
    .btns{
        #background: #FFFFFF;
        text-align: center;
        margin:10px 10px;
        /*border-bottom-left-radius: 8px;*/
        /*border-bottom-right-radius: 8px;*/
        /*border-top: 1px solid #DDDDDD;*/
    }
    .btns a{
        text-decoration: none;
        display: inline-grid;
        width: 100px;
        height: 30px;
        border-radius: 4px;
        border: 1px solid #EDEDED;
        line-height: 30px;
        text-align: center;
        font-size: 14px;
        color: #333333;
        margin-top: 5px;
    }
    .btns a+a{
        background: #4191FF;
        color: #FFFFFF;
        margin-left: 10px;
    }

    .jumpBtns{
        text-align: center;
        margin:10px 10px;
    }
    .jumpBtns a{
        display: inline-block;
        width: 220px;
        height: 30px;
        line-height: 30px;
        border-radius: 4px;
        border: 1px solid #EDEDED;
        text-align: center;
        font-size: 14px;
        color: #ffffff;
        background: #4191FF;
    }

    .creat{
        width: 50%;
        margin: 45% auto 0;
    }
    .linebox{
        width: 100%;
        height: 0.1rem;
        border-radius: 30px;
        background: #DDDDDD;
    }
    .line{
        height: 100%;
        border-radius: 30px;
        background: #4191ff;
        width: 0;
    }
    .creat p{
        text-align: center;
        font-size: 14px;
        margin-top: 0.2rem;
        color: #333333;
    }
    .paysucc{
        width: 100%;
        margin: 5% auto 0;

    }
    .paysucc h1{
        font-size: 16px;
        color: #333333;
        text-align: center;
        font-weight: normal;
        line-height: 0.6rem;
    }
    .payinfo{
        margin: 0 0.15rem;
        background: #f8f8f8;
    }
    .payinfocan{
        padding: 0.2rem;
    }
    .payinfo .item{
        font-size: 14px;
        color: #424242;
        line-height: 0.32rem;
    }
    .btnbox{
        border-top: 1px solid #DDDDDD;
        text-align: right;
        padding: 0.1rem 0.15rem 0.1rem 0;
    }
    .btnbox a{
        display: inline-block;
        width: 1rem;
        height: 0.3rem;
        border-radius: 4px;
        background: #4191ff;
        font-size: 14px;
        color: #FFFFFF;
        text-align: center;
        line-height: 0.3rem;
    }
    .btns_m {
        margin-top:0!important;
    }
    .payfail{
        width:100%;
        margin: 10% auto 0;
    }
    .payfail img{
        display: block;
        width: 100%;
        margin: auto;
    }
    .payfail p{
        font-size: 13px;
        color: #333333;
        text-align: center;
        margin: 0.2rem 0;
    }
    .payfail a{
        display: block;
        width: 1.45rem;
        height: 0.36rem;
        border-radius: 4px;
        line-height: 0.36rem;
        text-align: center;
        font-size: 14px;
        color: #FFFFFF;
        background: #4191ff;
        margin:0.1rem auto;
    }

    .mask{
        position: fixed;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        display: -webkit-flex;
        display: box;
        justify-content: center;
        align-items: center;
    }
    .tc{
        margin-top: -20%;
        max-width: 70%;
        border-radius: 8px;
        overflow: hidden;
    }
    .tc>h1{
        background: #4191FF;
        text-align: right;
        padding: 10px;
        overflow: hidden;
    }
    .tc h1 img{
        float: right;
        width: 14px;
        height: 14px;
    }
    .info{
        padding: 20px 40px;
        background: #FFFFFF;
    }
    .info h1{
        font-size: 14px;
        color: #FF4D4D;
        font-weight: normal;
        line-height: 20px;
    }
    .item{
        margin-top: 10px;
        white-space: nowrap;
    }
    .item span{
        font-size: 14px;
        color: #474747;
    }

    .paybox_tip{
        width: 100%;
        height: 600px;
        display:table;
        text-align: center;
        vertical-align:middle;
    }
    .paybox_tip h1{
        font-size: .3rem;
        line-height: 300px;
    }

    .paybox_tip p_box{
        text-align: center;
        width: auto;
        margin: auto;
    }

    .paybox_tip p{
        width: auto;
        display: block;
        text-align: center;
        line-height: .23rem;
        font-weight: bold;
        margin:0 auto;
    }


</style>


<script language="javascript">
    // 这个脚本是 ie6和ie7 通用的脚本
    function toClose(){
        if(confirm("您确定要关闭本页吗？")){
            window.opener=null;
            window.open('','_self');
            window.open('','_top');
            window.close();
        }
        else{
        }
    }
</script>
</body>
</html>
