$(function(){
    // 获得url参数
    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = window.location.search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]); return null; //返回参数值
    }
    var para = getUrlParam('isEdit');
    // isEdit有值说明是编辑
    if (para) {
        $('#add_edit_course h3').text('编辑章节');
    }
    // 根据文件类型选择，切换显示“文字”、“ppt”、“视频”上传框
    var radio = $('input[name="type"]');
    radio.change(function(){
        var index = radio.index(this);
        $('.toggleShow').eq(index).show().siblings('.toggleShow').hide();
    })
})
