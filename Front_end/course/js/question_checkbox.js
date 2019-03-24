$(function(){
})
function add_answer() {
    var num = $('#answers').children().length; // 已经有几个every_answers了
    $('#answers').append('<div class="every_answers"><input type="checkbox" name="which" value="' + (num + 1) + '"><input type="text" name="answer' + (num + 1) + '" class="form-control" placeholder="请输入答案"><span class="glyphicon glyphicon-trash" onclick="del_answer('+(num+1)+')"></span></div>')
}
// 删除第i个答案
function del_answer(i){
    $('.every_answers:nth-child('+i+')').remove();
    // 剩下的answer
    var answers = $('.every_answers');
    for(var i = 0; i < answers.length; i++) {
        $(answers[i]).find('[name=which]').attr('value', i+1);
        $(answers[i]).find('.form-control').attr('name', 'answer'+(i+1));
        $(answers[i]).find('.glyphicon.glyphicon-trash').attr('onclick', 'del_answer('+(i+1)+')');
    }
}