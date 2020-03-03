;layui.use(['laydate', 'form', 'layer', 'table', 'laytpl', 'layedit','jquery'], function () {
    var form = layui.form,
        layer = parent.layer === undefined ? layui.layer : top.layer,
        laytpl = layui.laytpl,
        table = layui.table,
        laydate = layui.laydate,
        layedit = layui.layedit,
        $ = layui.jquery;

    // 当前页面Bogy对象
    var $body = $('body');

    /**
     * 富文本编辑器
     */
    $.layedit = new function () {
        this.build = function (elem) {
            return layedit.build(elem); //建立编辑器
        };

        this.getContent = function (elem) {
            return layedit.getContent(elem);
        };

    };
    /**
     * 消息组件实例
     */
    $.msg = new function () {
        var self = this;
        this.shade = [0.02, '#000'];
        this.dialogIndexs = [];
        //关闭消息框
        this.close = function (index) {
            return layer.close(index);
        };

        //弹出警告消息框
        this.alert = function (msg, callback) {
            var index = layer.alert(msg, {end: callback, scrollbar: false});
            return this.dialogIndexs.push(index), index;
        };
        //确认对话框
        this.confirm = function (msg, ok, no) {
            var index = layer.confirm(msg, {title: '操作确认', btn: ['确认', '取消']}, function () {
                typeof ok === 'function' && ok.call(this);
            }, function () {
                typeof no === 'function' && no.call(this);
                self.close(index);
            });
            return index;
        };


        // 显示成功类型的消息
        this.success = function (msg, callback) {
            if (callback == undefined) {
                callback = function () {
                }
            }
            var index = layer.msg(msg, {icon: 1, shade: this.shade, scrollbar: false, time: 2000, shadeClose: true}, callback);
            return this.dialogIndexs.push(index), index;
        };
        //显示失败类型的消息
        this.error = function (msg, callback) {
            if (callback == undefined) {
                callback = function () {
                }
            }
            var index = layer.msg(msg, {icon: 2, shade: this.shade, scrollbar: false, time: 6000, shadeClose: true}, callback);
            return this.dialogIndexs.push(index), index;
        };
        //状态消息提示
        this.tips = function (msg, time, callback) {
            var index = layer.msg(msg, {time: (time || 3) * 1000, shade: this.shade, end: callback, shadeClose: true});
            return this.dialogIndexs.push(index), index;
        };
        //显示正在加载中的提示
        this.loading = function (msg, callback) {
            var index = msg ? layer.msg(msg, {icon: 16, scrollbar: false, shade: this.shade, time: 0, end: callback}) : layer.load(2, {time: 0, scrollbar: false, shade: this.shade, end: callback});
            return this.dialogIndexs.push(index), index;
        };

        this.prompt = function (msg,url,obj, callback) {
            var  index = layer.prompt({
                formType: 1,
                title: msg,
                close: false,
                btn: ['确定','取消']
            }, function (value,index1){
                if($.tool.isEmpty(value)) return false;
                layer.close(index1);
                obj.paypwd = $.md5(value);
                $.request.post(url,obj,function (res) {
                    callback(res);
                });
            });
            return this.dialogIndexs.push(index), index;
        };


    };

    /**
     * 常用工具
     */
    $.tool = new function () {
        //关闭当前弹出层
        this.close = function () {
            var index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
        }
        //刷新当前弹出层
        this.reload = function (type = '') {
            if (type == 'open') {
                var index = parent.layer.getFrameIndex(window.name);
                parent.location.reload();
            } else {
                window.location.reload();
            }
        }
        //判断数组是否为空
        this.isEmptyArray = function (array) {
            for (var x in array) {
                key = x;//键
                value = array[x];//值
                if (value != '') return false;
            }
            return true;
        }
        //判断字符串是否为空
        this.isEmpty = function (obj) {
            if(typeof obj == "undefined" || obj == null || obj == ""){
                return true;
            }else{
                return false;
            }
        }
    }


    /**
     * 重新封装表单插件
     */
    $.form = new function () {

        /**
         * 表单自动提交
         * @param elem
         * @param url
         * @param callback
         */
        this.on  = function (elem, url,callback,check) {

            form.render();
            //提交
            form.on('submit('+elem+')', function(obj){
                //验证参数
                if( typeof check === "function"){
                    var data = check(obj.field);
                    if(data.code == 0){
                        if(data.msg != ''){
                            $.msg.error(data.msg);
                        }
                        return false;
                    }
                    obj.field = data.data;
                }



                $.request.post(url,obj.field,function (res) {
                    callback(res);
                });
                return false;

            });

        }


        /**
         * 生成表单
         * @param elem 绑定表单id
         * @param url 链接
         * @param cols 表单渲染
         * @param page 表单渲染
         */
        this.table = function (elem, url, cols, isPage = true , done = '',limit = 20,  size = '', isTool = true) {
            if (!isPage) {
                var data = {
                    elem: '#' + elem + 'Table',
                    url: url,
                    cellMinWidth: 95,
                    height: "full-80",
                    limits: [500,3000],
                    limit: 500,
                    id: elem + 'TableId',
                    cols: cols,
                    done:function(res,curr,count){
                        if (done != '')  done(res,curr,count);
                    }
                };
            } else {
                var data = {
                    elem: '#' + elem + 'Table',
                    url: url,
                    cellMinWidth: 95,
                    page: true,
                    height: "full-80",
                    limits: [10, 15, 20, 25, 50, 100,200,500,3000],
                    limit: limit,
                    id: elem + 'TableId',
                    cols: cols,
                    done:function(res,curr,count){
                        if (done != '')  done(res,curr,count);
                    }
                };
            }
            if (size != '') data.size = size;
            if (size == 'lg') data.limit = 10;
            if (!isTool) data.height = "full-20";
            table.render(data);
        }

        /**
         * 表单搜索
         * @param TableId table编号
         * @param search 搜索内容
         * @param page 分页
         */
        this.search = function (TableId, search, page = 1) {
            console.log('搜索内容');
            console.log(search);
            var loading = $.msg.loading();
            if (!page) {
                var data = {
                    where: {search: search}
                };
            } else {
                var data = {
                    page: {curr: page},
                    where: {search: search}
                };
            }
            if (!$.tool.isEmptyArray(search)) {
                table.reload(TableId, data);
                $.msg.close(loading);
                $.msg.success('查询成功！');
            } else {
                $.msg.close(loading);
                $.tool.reload();
            }
        }

        /**
         * 修改表单字段值
         * @param tableName table名称
         * @param url 链接
         */
        this.editField = function (tableName, url) {
            table.on('edit(' + tableName + ')', function (obj) {
                var value = obj.value //修改后的值
                    , data = obj.data //所在行所有键值
                    , field = obj.field; //字段名称
                $.request.post(url, {
                    id: data.id,
                    field: field,
                    value: value,
                }, function (res) {
                    $.msg.success(res.msg);
                }, true);
                return false;
            });
        }

        /**
         * 修改按钮开关值
         * @param layFilter layFilter名称
         * @param url 链接
         */
        this.switch = function (layFilter, url) {
            form.on('switch(' + layFilter + ')', function (obj) {
                //乐观锁
                var verson = this.getAttribute('verson');
                if( $.tool.isEmpty(verson)){
                    var feild = {id: this.name};
                }else {
                    var feild = {id: this.name,verson:verson};
                }
                //提交
                $.request.get(url, feild, function (res) {
                    $.msg.success(res.msg);
                }, true);
                return false;
            });
        }

        this.radio = function (layFilter, url) {
            form.on('radio(' + layFilter + ')', function (obj) {
                //乐观锁
                var verson = this.getAttribute('verson');
                if( $.tool.isEmpty(verson)){
                    var feild = {id: this.name,value: this.value};
                }else {
                    var feild = {id: this.name,verson:verson,value: this.value};
                }
                //提交
                $.request.get(url, feild, function (res) {
                    $.msg.success(res.msg);
                }, true);
                return false;
            });
        }


        /**
         * 弹出新窗口
         * @param title 标题
         * @param url 链接
         * @param width 宽
         * @param height 高
         */
        this.open = function (title, url, width, height) {
            var index = layui.layer.open({
                title: title,
                type: 2,
                area: [width, height],
                content: url,
                success: function (layero, index) {
                    var body = layui.layer.getChildFrame('body', index);
                }
            })
            if (width == undefined || height == undefined) {
                layui.layer.full(index);
            }
        }

        /**
         * 渲染图片列表
         * @param type
         */
        this.imageRender = function (divId, type = 'one') {
            var $uploadParent = $("#" + divId);
            var $inputParent = $uploadParent.children('input');
            var $parent = $uploadParent.children('div');
            var url = $inputParent.attr('value');
            if (url == '' || url == undefined) return false;
            if (type == 'one') {
                var style = 'background-image: url("' + url + '");';
                $parent.attr('style', style);
                $parent.attr('data-upload-image', 'one');
            }
            else {
                var url_array = url.split("|"); //所有图片url数组
                var upload_id = $inputParent.attr('id');
                var upload_class = $parent.attr('class');
                var upload_style = $parent.attr('style');
                var uploadDiv = document.getElementById(divId);
                var upload_id_html = '<input type="hidden" id="' + upload_id + '" value="' + url + '">';
                var upload_image_html = '<div class="' + upload_class + '" data-upload-image="more" data-upload-id="' + upload_id + '" data-upload-div="' + divId + '" style="' + upload_style + '"> </div>';
                var html = '';
                //对新的所有图片url进行重新拼接
                $.each(url_array, function (index, value, arr) {
                    html = html + '<div class="' + upload_class + '" data-upload-url="' + value + '" style="background-image: url(' + value + ');"> <em class="layui-icon upload-icon-tip" style="float: right; display: none;">&#x1006;</em> </div>';
                })
                uploadDiv.innerHTML = upload_id_html + html + upload_image_html;
            }
            $.form.imageListen();
            return false;
        }

        /**
         * 上传图片监听器
         * @param id
         */
        this.imageListen = function (id) {
            //对删除图片的显示隐藏操作
            $(".uploadimage").hover(function () {
                $(this).children('em').show();
            }, function () {
                $(this).children('em').hide();
            })

            //删除图片操作
            $('.upload-icon-tip').on('click', function () {
                //获取操作元素对象
                var $parent = $(this).parent('div');
                var $uploadParent = $parent.parent('div');
                var $inputParent = $uploadParent.children('input');

                var current_upload_url = $parent.attr('data-upload-url'); //当前图片url
                var all_upload_url = $inputParent.attr('value'); //所有图片url
                var all_upload_url_array = all_upload_url.split("|"); //所有图片url数组
                var all_upload_url_new = ''; //新的所有图片url

                //对新的所有图片url进行重新拼接
                $.each(all_upload_url_array, function (index, vaule, arr) {
                    if (vaule != current_upload_url) {
                        if (all_upload_url_new == '') {
                            all_upload_url_new = vaule;
                        } else {
                            all_upload_url_new = all_upload_url_new + '|' + vaule;
                        }
                    }
                })

                //进行图片删除操作
                var dialogIndex = $.msg.confirm('确定要移除这张图片吗？', function () {
                    $.msg.close(dialogIndex);
                    $inputParent.attr('value', all_upload_url_new);
                    $parent.remove();
                });
            });
        }
    }

    /**
     * 时间插件
     */
    $.laydate = new function () {
        var self = this;
        //年选择器
        this.year = function (elem) {
            laydate.render({elem: '#' + elem, type: 'year'});
        }
        //年月选择器
        this.month = function (elem) {
            laydate.render({elem: '#' + elem, type: 'month'});
        }
        //时间选择器
        this.time = function (elem) {
            laydate.render({elem: '#' + elem, type: 'time'});
        }
        //日期时间选择器
        this.datetime = function (elem) {
            laydate.render({elem: '#' + elem, type: 'datetime'});
        }
        //日期范围
        /*        this.range = function (elem) {
         laydate.render({elem: '#' + elem, range: true});
         }*/


        //日期范围
        this.range = function (elem, value = '') {
            if (value !== '') {
                var nowTime = new Date();
                var date = (new Date()).Format("yyyy-MM-dd");
                nowTime.setDate(nowTime.getDate() - value);
                date = nowTime.Format("yyyy-MM-dd") + ' - ' + date;

                laydate.render({elem: '#' + elem, range: true, value: date});
            } else {
                laydate.render({elem: '#' + elem, range: true});
            }
        }

    }

    // 对Date的扩展，将 Date 转化为指定格式的String
    // 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符，
    // 年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字)
    // 例子：
    // (new Date()).Format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423
    // (new Date()).Format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18
    Date.prototype.Format = function (fmt) { //author: meizz
        var o = {
            "M+": this.getMonth() + 1, //月份
            "d+": this.getDate(), //日
            "h+": this.getHours(), //小时
            "m+": this.getMinutes(), //分
            "s+": this.getSeconds(), //秒
            "q+": Math.floor((this.getMonth() + 3) / 3), //季度
            "S": this.getMilliseconds() //毫秒
        };
        if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
        for (var k in o)
            if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
        return fmt;
    }


    /**
     * 注册 data-open 事件
     * 用于打开弹出层
     */
    $body.on('click', '[data-open]', function () {
        var width = $(this).attr('data-width');
        var height = $(this).attr('data-height');
        if($.tool.isEmpty(width)){
            width = '80%';
        }
        if($.tool.isEmpty(height)){
            height = '80%';
        }

        $.form.open($(this).attr('data-title'), $(this).attr('data-open'),width, height);
    })

    /**
     * 注册 data-open 事件
     * 用于关闭弹出层
     */
    $body.on('click', '[data-close]', function () {
        var index = parent.layer.getFrameIndex(window.name);
        parent.layer.close(index);
    })


    /**
     * 注册 data-export 事件
     * 用于表格搜索
     */
    $body.on('click', '[data-export]', function () {
        var searchData = new Array();
        var searchInput = $('#searchBlock div div input');
        var searchSelect = $('#searchBlock div div select');
        $.each(searchInput, function (i, obj) {
            id = $(obj).attr('id');
            if (id != undefined) {
                searchData[id] = $("#" + id).val();
            }
        });
        $.each(searchSelect, function (i, obj) {
            id = $(obj).attr('id');
            if (id != undefined) {
                searchData[id] = $("#" + id).val();
            }
        });

        var url = $(this).attr('data-export');

        formCall( 'get',url, searchData, '_blank');
        return false;

    });

    /**
     * 注册 data-check-all 事件
     */
    $body.on('click', '[data-check-all]', function () {
        var url = $(this).attr('data-check-all');
        var checkStatus = table.checkStatus($(this).attr('data-table-id')),
            data = checkStatus.data,
            id = [];

        for (let i in data) {
            id.push(data[i].id);
        }
        var Index = $.msg.confirm($(this).attr('data-title'), function () {
            $.msg.close(Index);
            $.request.get(url, {id: id}, function (res) {
                $.msg.success(res.msg, function () {
                    $.tool.reload();
                })
            })
        });

        return false;
    });

    /**
     * 注册 data-check-all1 事件
     */
    $('body').on('click', '[data-check-all1]', function () {
        var url = $(this).attr('data-check-all1');
        var checkStatus = table.checkStatus($(this).attr('data-table-id')),
            data = checkStatus.data,
            id = [];
         url = url + '?test=1';
        for (let i in data) {
            id.push(data[i].id);
            url += '&id[]='+data[i].id;
        }

        if(id.length < 1){
            $.msg.error('请选择一条代付订单！！');
            return false;
        }
        if(id.length > 10){
            $.msg.error('不能超过10笔代付订单！！');
            return false;
        }


        var Index = $.msg.confirm($(this).attr('data-title'), function () {
            $.msg.close(Index);

            var index = layui.layer.open({
                title: '批量处理——选择代付通道',
                type: 2,
                area: ['100%', '80%'],
                content: url,
                success: function (layero, index) {
                    var body = layui.layer.getChildFrame('body', index);
                }
            })

        });

        return false;
    });


    /**
     * 批量删除
     * 注册 data-del-all 事件
     */
    $body.on('click', '[data-del-all]', function () {
        var url = $(this).attr('data-del-all');
        var checkStatus = table.checkStatus($(this).attr('data-table-id')),
            data = checkStatus.data,
            id = [];
        if (data.length > 0) {
            for (let i in data) {
                id.push(data[i].id);
            }
            var Index = $.msg.confirm($(this).attr('data-title'), function () {
                $.msg.close(Index);
                $.request.get(url, {id: id}, function (res) {
                    $.msg.success(res.msg, function () {
                        $.tool.reload();
                    })
                })
            });
        } else {
            $.msg.error('请选择需要删除的信息!');
        }
        return false;
    });

    /**
     * 单个删除
     * 注册 data-del 事件
     */
    $body.on('click', '[data-del]', function () {
        var url = $(this).attr('data-del');
        var Index = $.msg.confirm($(this).attr('data-title'), function () {
            $.msg.close(Index);
            $.request.get(url, {}, function (res) {
                $.msg.success(res.msg, function () {
                    $.tool.reload();
                })
            })
        });
        return false;
    });
    /**
     * 单个删除
     * 注册 data-del 事件
     */
    $body.on('click', '[data-prompt]', function () {

        var url = $(this).attr('data-prompt');
        var text = $(this).attr('data-text');
        var Index = $.msg.confirm($(this).attr('data-title'), function () {
            $.msg.close(Index);
            layer.prompt({title: text, formType: 2}, function(text, index){
                layer.close(index);
                url += '&text='+text;
                $.request.get(url, {}, function (res) {
                    $.msg.success(res.msg, function () {
                        $.tool.reload();
                    })
                })
            });
        });
        return false;
    });
    /**
     * 单个刷新
     * 注册 data-check 事件
     */
    $body.on('click', '[data-check]', function () {
        var url = $(this).attr('data-check');

        var Index =  $.msg.confirm($(this).attr('data-title'), function () {
            $.msg.close(Index);
            $.request.get(url, {}, function (res) {
                $.msg.success(res.msg, function () {
                    $.tool.reload();
                })
            })
        });
        return false;
    });


    /**
     * 跳转新页面
     * 注册 data-href 事件
     */
    $body.on('click', '[data-href]', function () {
        var url = $(this).attr('data-href');
        var text = $(this).attr('data-title');
        if(top.layui.index){
            top.layui.index.openTabsPage(url,text);
        }else{
            window.open(url);
        }
        return false;
    });



    /**
     * 放大图片
     */
    $body.on('click', '[data-image]', function () {
        layer.photos({
            photos: $(this).parents('tr'),
            anim: 5
        });
        return false;
    });

    /**
     * 多图片上传放大图片
     */
    $body.on('click', '[data-upload-url]', function () {
        var url = $(this).attr('data-upload-url');
        console.log(url);
        var img = new Image();
        img.src = url;
        var width = img.width + 'px';
        var height = (img.height + 45) + 'px';
        if (url == '' || url == undefined) {
            $.form.msg('数据有误！');
            return false;
        } else {
            layer.open({
                title: "查看图片",
                type: 2,
                area: [width, height],
                content: url,
            })
        }
        return false;
    });

    /**
     * 注册 data-search 事件
     * 用于表格搜索
     */
    $body.on('click', '[data-search]', function () {
        var searchData = Object();
        var searchInput = $('#searchBlock div div input');
        var searchSelect = $('#searchBlock div div select');
        $.each(searchInput, function (i, obj) {
            id = $(obj).attr('id');
            if (id != undefined) {
                searchData[id] = $("#" + id).val();
            }
        });
        $.each(searchSelect, function (i, obj) {
            id = $(obj).attr('id');
            if (id != undefined) {
                searchData[id] = $("#" + id).val();
            }
        });
        $.form.search($(this).attr('data-search'), searchData);
        return false;
    });

    /**
     * 注册 data-upload-image 事件
     * 表单图片上传
     */
    $body.on('click', '[data-upload-image]', function () {
        var upload_type = $(this).attr('data-upload-image'),
            upload_id = $(this).attr('data-upload-id'),
            upload_class = $(this).attr('class'),
            upload_src = $(this).attr('src'),
            upload_style = $(this).attr('style'),
            upload_url = '/api/admin.common/uploadIamge',
            divId = $(this).attr('data-upload-div');
        if (upload_type == 'one') {
            var title = '上传单图片';
            var url = upload_url + "?type=one";
        } else {
            var title = '上传多图片';
            var url = upload_url + "?type=multi";
        }
        var uploadImage = layer.open({
            title: title,
            type: 2,
            area: ['700px', '450px'],
            content: url,
            success: function (layero, selectIcon) {
                var body = layer.getChildFrame('body', uploadImage);
            },
            end: function () {
                var upload_iamges = window.sessionStorage.getItem("upload_iamges");
                console.log(upload_iamges);
                if (upload_iamges != null) {
                    if (upload_type == 'one') {
                        $('#' + upload_id).attr('value', upload_iamges);
                        $(this).attr('src', upload_iamges);

                        //获取隐藏的div
                        var upload_id_html = '<input type="hidden" id="' + upload_id + '" value="' + upload_iamges + '">';
                        //重新渲染显示层
                        var upload_image_html = '<div class="' + upload_class + '" data-upload-image="one" data-upload-id="' + upload_id + '" data-upload-div="' + divId + '" style="background-image: url(' + upload_iamges + ');"> </div>';

                        //插入到html内
                        var uploadDiv = document.getElementById(divId);
                        uploadDiv.innerHTML = upload_id_html + upload_image_html;

                    } else {
                        var upload_url = $('#' + upload_id).attr('value');
                        if (upload_url == '') {
                            $('#' + upload_id).attr('value', upload_iamges);
                        } else {
                            upload_iamges = upload_url + '|' + upload_iamges;
                            $('#' + upload_id).attr('value',upload_iamges);
                        }

                        var uploadDiv = document.getElementById(divId);
                        var upload_id_html = '<input type="hidden" id="' + upload_id + '" value="' + upload_iamges + '">';
                        var upload_image_html = '<div class="' + upload_class + '" data-upload-image="more" data-upload-id="' + upload_id + '" data-upload-div="' + divId + '" style="' + upload_style + '"> </div>';
                        var html = '';

                        //切割图片重新生成写入
                        arr = upload_iamges.split("|");
                        arr.forEach(function (value, i) {
                            html = html + '<div class="' + upload_class + '" data-upload-url="' + value + '" style="background-image: url(' + value + ');"> <em class="layui-icon upload-icon-tip" style="float: right; display: none;">&#x1006;</em> </div>';
                        });

                        uploadDiv.innerHTML = upload_id_html + html + upload_image_html;
                        $.form.imageListen();
                    }
                }
                window.sessionStorage.removeItem("upload_iamges");
            }
        })
        return false;
    });

    /**
     * 封装请求
     */
    $.request = new function () {
        var self = this;
        //post请求
        this.post = function (url, data, callback, isReload = false) {
            request('POST', url, data, callback, isReload);
        }
        //get请求
        this.get = function (url, data, callback, isReload = false) {
            request('GET', url, data, callback, isReload);
        }
    }

    /**
     * AJAX请求
     * @param type
     * @param url
     * @param data
     * @param callback
     */
    function request(type, url, data, callback, isReload = true) {
        //$.msg.loading('正在加载，请稍等！');

        var fag = true;
        if(!$.isEmptyObject(window.word)){

            for (var i in window.word) {
                if(!$.tool.isEmpty(window.word[i]) && url.indexOf(window.word[i]) > -1){
                    layer.prompt({title: '输入口令，并确认', formType: 1}, function(pass, index){
                        if(!$.tool.isEmpty(pass)){
                            data.word = pass;
                            layer.close(index);
                            $.ajax({
                                url: url,
                                type: type,
                                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                                dataType: "json",
                                data: data,
                                timeout: 60000,
                                success: function (res) {
                                    log_ajax(type, url, data, res);
                                    $.msg.close();
                                    if (res.code == 1) {
                                        callback(res);
                                    } else {
                                        if (isReload == true) {
                                            $.msg.error(res.msg, function () {
                                                $.tool.reload();
                                            });
                                        } else {
                                            $.msg.error(res.msg);
                                        }
                                    }
                                },
                                error: function (xhr, textstatus, thrown) {
                                    console.log(xhr);

                                    if (isReload == true) {
                                        $.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！', function () {
                                               $.tool.reload();
                                        });
                                    } else {
                                        $.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！');
                                    }
                                }
                            });

                        }
                    });
                    fag = false;
                    break;
                }
            }
        }

        if(fag){
            $.ajax({
                url: url,
                type: type,
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                dataType: "json",
                data: data,
                timeout: 60000,
                success: function (res) {
                    log_ajax(type, url, data, res);
                    $.msg.close();
                    if (res.code == 1) {
                        callback(res);
                    } else {
                        if (isReload == true) {
                            $.msg.error(res.msg, function () {
                                $.tool.reload();
                            });
                        } else {
                            $.msg.error(res.msg);
                        }
                    }
                },
                error: function (xhr, textstatus, thrown) {
                    console.log(xhr);

                    if (isReload == true) {
                        $.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！', function () {
                               $.tool.reload();
                        });
                    } else {
                        $.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！');
                    }
                }
            });
        }
    }


    /**
     * js Post提交
     * @param type
     * @param url
     * @param params
     * @param target
     * @returns {boolean}
     */
    function formCall(type = 'post', url, params, target = '_blank'){

        var tempform = document.createElement("form");
        tempform.action = url;
        tempform.method = type;
        tempform.style.display="none";
        if(target) {
            tempform.target = target;
        }
        for (var x in params) {
            var opt = document.createElement("input");
            opt.name = x;
            opt.value = params[x];
            tempform.appendChild(opt);
        }
        var opt = document.createElement("input");
        opt.type = "submit";
        tempform.appendChild(opt);
        document.body.appendChild(tempform);
        tempform.submit();
        document.body.removeChild(tempform);
    }

    /**
     * 记录AJAX请求
     * @param type
     * @param url
     * @param data
     * @param res
     */
    function log_ajax(type, url, data, res) {
        console.log('======================================');
        console.log(type + '请求：' + url);
        console.log('---------------请求数据---------------');
        console.log(JSON.stringify(data));
        console.log('---------------返回结果---------------');
        console.log(res);
        console.log('======================================');
    }

});