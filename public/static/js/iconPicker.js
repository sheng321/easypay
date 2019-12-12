layui.extend({
    iconPicker: "/static/js/lay-module/iconPicker/iconPicker",
}).use([ 'jquery','iconPicker'], function () {
    var iconPicker = layui.iconPicker,
        $ = layui.jquery;

    iconPicker.render({
        // 选择器，推荐使用input
        elem: '#iconPicker',
        // 数据类型：fontClass/unicode，推荐使用fontClass
        type: 'fontClass',
        // 是否开启搜索：true/false
        search: true,
        // 是否开启分页
        page: false,
        // 每页显示数量，默认12
        limit: 140,
        // 点击回调
        click: function (data) {
            var icon = data.icon;
            $('#icon').attr('value', icon.substr(11,11 + icon.length));
        }
    });

});

