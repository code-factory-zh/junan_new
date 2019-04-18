$(function(){
    getPaper();
})
function getPaper(){
    $.get("http://course.junan.com/manage/exam/score_report?exam_question_id=85", function(res,status){
        if ('success' == status) {
            console.log(res)
            if (res.code === 0) {
                $('#title span').html(res.data.exam_detail.couse_name);
                $('#name span').html(res.data.exam_detail.user_name);
            } else {
                alert(res.msg);
            }
        } else {
            console.log('请求失败');
        }
    });
}