<?php
// 자동 반납을 점검하는 날짜를 time에 저장
// 정오마다 반납이 진행되기 때문에 그 때에 맞춰 자동반납을 진행하기 위함
session_start();
$_SESSION["time"] = date('Y/m/d');
// 밑의 주석은 자동 반납 데모를 위한 코드로 자동반납이 실행되도록 시간을 전날로 설정
// $_SESSION["time"] = date('Y/m/d', strtotime('-1 day'));

// 디비 연결
$tns = "
(DESCRIPTION=
     (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
     (CONNECT_DATA= (SERVICE_NAME=XE))   
 )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';

try {    
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {    
    echo("에러 내용: ".$e -> getMessage());
}

// 반납 날짜를 통해 자동 반납이 진행되어야할 도서들을 select함
$stmt = $conn -> prepare("SELECT ISBN, CNO, DATERENTED
    FROM EBOOK
    WHERE TO_CHAR(DATEDUE, 'YYYYMMDD') < TO_CHAR(SYSDATE, 'YYYYMMDD')
    ");

$stmt -> execute();

// 이전 대여 기록에 반납된 도서에 대한 데이터 삽입
$stmt_prev = $conn -> prepare('INSERT INTO PREVIOUSRENTAL(ISBN, DATERENTED, DATERETURNED, CNO) 
                                VALUES(:isbn, :daterented, :datereturned, :id)');

// 예약 후 대출 안해서 기간 지난 고객의 예약 삭제
$stmt_reserve_remove = $conn -> prepare('DELETE FROM RESERVE WHERE CNO = :cno and ISBN = :isvn');

// 반납 도서에 대해 제일 먼저 예약한 고객의 번호 반환 
$stmt_reserve = $conn->prepare('SELECT CNO 
                                FROM (SELECT CNO FROM RESINFO 
                                        WHERE ISBN = :isbn ORDER BY DATETIME) T 
                                WHERE ROWNUM = 1');

// 자동 반납 시 EBOOK 테이블에 도서에 대한 대출 정보를 없애기 위한 오라클 코드 
$stmt_return = $conn -> prepare("UPDATE EBOOK 
                            SET CNO = :cno, EXTTIMES = NULL, DATERENTED = NULL, DATEDUE = :datedue 
                            WHERE ISBN = :isbn
                        ");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    //반납될 도서 목록에 대해 위의 오라클 코드를 실행
    $isbn = $row["ISBN"];
    $daterented = $row["DATERENTED"];
    $datereturned = date("Y/m/d");
    $cno = $row["CNO"];
    if($daterented == null){
        // 예약 후 대출 가능한데 기간 지남
        // 예약 삭제
        $stmt_reserve_remove(array($cno, $isbn));
    }else{
        // 반납할 책 반납 기록
        $stmt_prev -> execute(array($isbn, $daterented, $datereturned, $cno));
    }
    $stmt_reserve->execute(array($isbn));
    $col = $stmt_reserve -> fetch(PDO::FETCH_ASSOC);
    // cno를 확인하여 예약자가 있다면 mailer.php로 이동하여 예약자 메일 전송
    if($col['CNO'] != null){
        $stmt_return -> bindParam(':cno', $col['CNO']);
        $stmt_return -> bindParam(':datedue', date("Y/m/d"));
        $stmt_return -> bindParam(':isbn', $isbn);
        $stmt_return -> execute();
        $_SESSION["CNO"] = $col['CNO'];
        echo "<script>location.href='mailer.php?isbn=$isbn';</script>";
    }
    else{
        $stmt_return -> bindParam(':cno', null);
        $stmt_return -> bindParam(':datedue', null);
        $stmt_return -> bindParam(':isbn', $isbn);
        $stmt_return -> execute();
    }
}
// 반납할 도서가 없다면 이전 페이지로 돌아감
$_SESSION['check'] = 'yes';
echo "<script>history.back();</script>"
?>