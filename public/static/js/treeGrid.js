layui.extend({
    treeGrid: "/static/js/lay-module/treeGrid/treeGrid",
}).use([ 'jquery','treeGrid'], function () {
    var treeGrid = layui.treeGrid,
        $ = layui.jquery;
    // 当前页面Bogy对象
    var $body = $('body');
    /**
     * 生成树形表单
     * @param elem 绑定表单id
     * @param url 链接
     * @param cols 表单渲染
     * @param treeShowName 以树形式显示的字段
     * @param page 表单渲染
     */
    $.form.tableTree = function (elem, url, cols, treeShowName = 'title',isPage = true, skin = 'line', size = '', isTool = true) {
        if (!isPage) {
            var data = {
                id: elem + 'TableId'
                , elem: '#' + elem + 'Table'
                , url: url
                , method: 'get'
                , cellMinWidth: 95
                , idField: 'id'//必須字段
                , treeId: 'id'//树形id字段名称
                , treeUpId: 'pid'//树形父id字段名称
                , treeShowName: treeShowName//以树形式显示的字段
                , height: "full-80"
                , isFilter: false
                , iconOpen: false//是否显示图标【默认显示】
                , isOpenDefault: true//节点默认是展开还是折叠【默认展开】
                , loading: true
                , cols: cols
                , isPage: false
                , parseData: function (res) {
                    return res;
                }
            };
        } else {
            var data = {
                id: elem + 'TableId'
                , elem: '#' + elem + 'Table'
                , url: url
                , method: 'get'
                , cellMinWidth: 95
                , idField: 'id'//必須字段
                , treeId: 'id'//树形id字段名称
                , treeUpId: 'pid'//树形父id字段名称
                , treeShowName: treeShowName//以树形式显示的字段
                , height: "full-80"
                , isFilter: false
                , iconOpen: false//是否显示图标【默认显示】
                , isOpenDefault: true//节点默认是展开还是折叠【默认展开】
                , loading: true
                , limits: [10, 15, 20, 25, 50, 100]
                , limit: 10
                , cols: cols
                , isPage: true
                , parseData: function (res) {
                    return res;
                }
            };
        }

        if (skin != '') data.skin = skin;
        if (size != '') data.size = size;
        if (size == 'lg') data.limit = 10;
        if (!isTool) data.height = "full-40";

        treeGrid.render(data);
    }

    $.form.searchTree = function (TableId, search, page = 1) {
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
            treeGrid.reload(TableId, data);
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
    $.form.editFieldTree = function (tableName, url) {
        treeGrid.on('edit(' + tableName + ')', function (obj) {
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
     * 注册 data-search-tree 事件
     * 用于表格搜索
     */
    $body.on('click', '[data-search-tree]', function () {
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

        $.form.searchTree($(this).attr('data-search-tree'), searchData);
        return false;
    });
    /**
     * 批量删除
     * 注册 data-del-all-tree 事件
     */
    $body.on('click', '[data-del-all-tree]', function () {
        var url = $(this).attr('data-del-all-tree');
        var checkStatus = treeGrid.checkStatus($(this).attr('data-table-id')),
            data = checkStatus.data,
            id = [];

        if (data.length > 0) {
            for (let i in data) {
                id.push(data[i].id);
            }

            $.msg.confirm($(this).attr('data-title'), function () {
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

});

