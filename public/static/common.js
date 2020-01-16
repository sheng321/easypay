//判断主流浏览器版本  //待完善
function browserVersion() {
    var userAgent = navigator.userAgent; //取得浏览器的userAgent字符串
    var isIE = userAgent.indexOf("compatible") > -1 && userAgent.indexOf("MSIE") > -1; //判断是否IE<11浏览器
    var isIE11 = userAgent.indexOf('Trident') > -1 && userAgent.indexOf("rv:11.0") > -1;
    var isEdge = userAgent.indexOf("Edge") > -1 && !isIE; //Edge浏览器
    var isFirefox = userAgent.indexOf("Firefox") > -1; //Firefox浏览器
    var isOpera = userAgent.indexOf("Opera")>-1 || userAgent.indexOf("OPR")>-1 ; //Opera浏览器
    var isChrome = userAgent.indexOf("Chrome")>-1 && userAgent.indexOf("Safari")>-1 && userAgent.indexOf("Edge")==-1 && userAgent.indexOf("OPR")==-1; //Chrome浏览器
    var isSafari = userAgent.indexOf("Safari")>-1 && userAgent.indexOf("Chrome")==-1 && userAgent.indexOf("Edge")==-1 && userAgent.indexOf("OPR")==-1; //Safari浏览器
    if(isIE) {
        var reIE = new RegExp("MSIE (\\d+\\.\\d+);");
        reIE.test(userAgent);
        var fIEVersion = parseFloat(RegExp["$1"]);
        if(fIEVersion == 7) {
            //return 'IE7';
            return false;
        } else if(fIEVersion == 8) {
           // return 'IE8';
            return false;
        } else if(fIEVersion == 9) {
           // return 'IE9';
            return true;
        } else if(fIEVersion == 10) {
           // return 'IE10';
            return true;
        } else {
           // return 'IE6';//IE版本<7
            return false;
        }
    } else if(isIE11) {
       // return 'IE11';
        return false;
    } else if(isEdge) {
       // return 'Edge'+userAgent.split('Edge/')[1].split('.')[0];
        return true;
    } else if(isFirefox) {
        //return 'Firefox'+userAgent.split('Firefox/')[1].split('.')[0];
        return true;
    } else if(isOpera) {
       // return 'Opera'+userAgent.split('OPR/')[1].split('.')[0];
        return true;
    } else if(isChrome) {
       // return 'Chrome'+userAgent.split('Chrome/')[1].split('.')[0];
        if(userAgent.split('Chrome/')[1].split('.')[0] < 56){
            return false;
        }
        return true;
    } else if(isSafari) {
      //  return 'Safari';+userAgent.split('Safari/')[1].split('.')[0];

        return true;
    } else{
        return -1;//不是ie浏览器
        return false;
    }
}

//是否低版本
$low =  browserVersion()
if(!$low){
    document.write("<div style='position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 10000; width: 100%; height: 100%; padding-top: 200px;  background-color: #fff'><P  style='font-size: 50px; text-align: center'>请使用最新版本的主流浏览器！</P></div>");
}






