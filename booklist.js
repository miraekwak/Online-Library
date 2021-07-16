// booklist의 검색 조건 체크박스의 체크 여부에 따라 비활성화, 활성화하기 위한 코드
$('.chk').click(function(){
    if($(this).is(':checked')){
        $(this).next().nextAll().attr('disabled', false)
    }
    else{
        $(this).next().nextAll().attr('disabled', true)
    }
});

