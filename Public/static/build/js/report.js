$(function(){
    getPaper();
})
var arr = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
// 将字符串数字答案变成字母答案
function getLetters(str){
    str = str || ''
    var res = ''
    for (var i = 0; i < str.length; i++) {
        var index = parseInt(str[i]) - 1;
        res += arr[index];
    }
    return res;
}
function getPaper(){
    $.get("http://course.junan.com/manage/exam/score_report?exam_question_id=85", function(res,status){
        if ('success' == status) {
            console.log(res)
            if (res.code === 0) {
                $('#title span').html(res.data.exam_detail.couse_name);
                $('#name span').html(res.data.exam_detail.user_name);
                $('#idcard').html(res.data.exam_detail.id_card);
                $('#phone').html(res.data.exam_detail.mobile);
                $('#num1').html(res.data.score_detail.my_score);
                $('#num2').html(res.data.score_detail.total_score);
                $('#num3').html(res.data.score_detail.total_score);
                $('#num4').html(res.data.score_detail.total_questions);
                $('#num5').html(res.data.score_detail.my_rank);
                $('#num6').html(res.data.score_detail.join_users);
                var list = res.data.question_detail || [];
                var str = ''
                for (var i = 0; i < list.length; i++) {
                    str += '<div class="item">' +
                        '<div class="ask">' + (i+1) + '.' + list[i].title +
                        '</div>' +
                        '<div class="score_amount">【分值：' + list[i].score +
                        '】</div><div class="answers">';
                    var options = list[i].option;
                    for (var j = 0; j < options.length; j++) {
                        str += '<div class="every">' + arr[j] + '.' + options[j] +
                            '</div>'
                    }
                    str += '</div><div class="result"><span class="rightAnswer">正确答案：' + getLetters(list[i].answer) +
                        '</span><span class="yourAnswer">';
                    if (list[i].status == 1) {
                        str += '<img src="/static/assets/images/right.png" alt="">'
                    } else {
                        str += '<img src="/static/assets/images/false.png" alt="">'
                    }
                    str += '您的回答：' + getLetters(list[i].my_answer) +
                        '</span><span class="getScore">（得分：';
                    if (list[i].status == 1) {
                        str += '<i class="blue">'
                    } else {
                        str += '<i>'
                    }
                    str += list[i].my_score +
                        '</i>）</span></div><div class="line"></div></div>';
                }
                $('.paper_content').html(str);
            } else {
                alert(res.msg);
            }
        } else {
            console.log('请求失败');
        }
    });
}