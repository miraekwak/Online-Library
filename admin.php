<?php
// 자동 반납을 위한 코드로 날짜가 달라질 때 마다 실행됨
// 도서에 대한 통계가 진행되므로 자동반납 프로세스를 확인함
// session에 저장된 time변수의 date값을 보고 실행할지 말지 결정
// autoreturn.php를 실행하여 자동 반납을 실행함
session_start();
$time = date('Y/m/d', strtotime('+1 day'));
// $time = $_SESSION['time'] ?? date('Y/m/d', strtotime('-1 day'));
if(strtotime($time) < strtotime(date('Y/m/d'))){
    echo "<script>location.href='autoreturn.php';</script>";
}

//디비 연결
$tns = "
(DESCRIPTION=
     (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
     (CONNECT_DATA= (SERVICE_NAME=XE))   
 )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';

$id = $_SESSION["id"];

try {    
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {    
    echo("에러 내용: ".$e -> getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"> <!-- JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <link rel=”stylesheet” href=”http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css“>
    <link rel="stylesheet" href="list.css">
    <title>Statistics Management</title>
</head>

<body>
    <!--프로젝트 명과 로그아웃을 위한 div-->
    <div class="jumbotron">
        <h1 class="text-center">Online Library</h1>
        <a class="btn btn-sm btn-outline-dark float-right" href="login.php">LOGOUT</a>
    </div>

    <!--관리자 표시-->
    <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
        <a href="#" class="navbar-brand">관리자 님</a>
    </nav>

    <!--통계 내용 div-->
    <div class="container">
        <!--페이지 제목-->
        <div class="page-header">
            <h2>
                < 통계 관리>
            </h2>
        </div>
        <hr>
        <!--첫번째 통계로 21년에 대출된 도서 목록을 보여줌-->
        <div class="page-header">
            <h4>2021년 대출 도서</h4>
        </div>
        <hr>
        <!--테이블을 통해 나타냄-->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>제목</th>
                    <th>대출일</th>
                    <th>대출인 번호</th>
                </tr>
            </thead>
            <tbody>
                <?php
                //union을 사용하여 과거 대여기록과 현재 대출 중이 도서를 select
                $stmt = $conn -> prepare("SELECT E.ISBN, E.TITLE, P.DATERENTED, P.CNO
                            FROM PREVIOUSRENTAL P JOIN EBOOK E
                            ON P.ISBN = E.ISBN
                            WHERE TO_CHAR(P.DATERENTED, 'YYYY') = '2021'
                            UNION
                            SELECT ISBN, TITLE, DATERENTED, CNO
                            FROM EBOOK
                            WHERE TO_CHAR(DATERENTED, 'YYYY') = '2021'");
                $stmt -> execute();
                // 하나씩 fetch하여 행 생성 
                while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
?>
                <tr>
                    <td><?=$row['ISBN']?></td>
                    <td>
                        <!--제목 클릭 시 도서 정보를 보여주기 위한 링크-->
                        <a href="bookview.php?isbn=<?=$row['ISBN']?>"><?=$row['TITLE']?></a>
                    </td>
                    <td><?= $row['DATERENTED'] ?></td>
                    <td><?= $row['CNO'] ?></td>
                </tr>
                <?php
}
?>
            </tbody>
        </table>

        <!--출판년도 별 출판된 도서의 개수와 대여된 횟수를 표시함-->
        <div class="page-header">
            <h4>출판년도 별 도서 대여 횟수</h4>
        </div>
        <hr>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>출판년도</th>
                    <th>출판 책 수</th>
                    <th>총 대여 횟수</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // group by rollup을 사용하여 년도 별로 그룹핑 후 도서 개수, 대여 횟수를 count함
                    $stmt = $conn -> prepare("SELECT TO_CHAR(E.YEAR, 'YYYY') AS YEAR, 
                                                COUNT(DISTINCT E.ISBN) AS BOOK_CNT, 
                                                COUNT(P.ISBN) AS RENTAL_CNT
                                            FROM EBOOK E LEFT JOIN PREVIOUSRENTAL P
                                            ON E.ISBN = P.ISBN
                                            GROUP BY ROLLUP(YEAR)");
                    $stmt -> execute();
                // fetch하여 행으로 만들음
                while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
?>
                <tr>
                    <td><?=$row['YEAR']?></td>
                    <td><?= $row['BOOK_CNT'] ?></td>
                    <td><?= $row['RENTAL_CNT'] ?></td>
                </tr>
                <?php
}
?>
            </tbody>
        </table>

        <!--현재 예약 중인 도서에 대해 예약자의 평균 대기일 계산-->
        <div class="page-header">
            <h4>현재 예약자들의 평균 대기일</h4>
        </div>
        <hr>
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>평균 대기일</th>
                    <th>총 예약 인원</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // reserve와 ebook테이블을 join하고 윈도우 함수를 사용하여 
                // isbn으로 partition된 값들의 대기일 평균, 인원수를 구함
                $stmt = $conn -> prepare("SELECT DISTINCT R.ISBN,
                                    AVG(ROUND(TO_DATE(TO_CHAR(E.DATEDUE, 'YYMMDD'))-
                                            TO_DATE(TO_CHAR(R.DATETIME,'YYMMDD'))))
                                    OVER(PARTITION BY R.ISBN) AS DELAYDAY,
                                    COUNT(R.CNO) OVER(PARTITION BY R.ISBN) AS CNT
                                FROM RESERVE R, EBOOK E
                                WHERE R.ISBN = E.ISBN
                                ORDER BY 1");
                $stmt -> execute();
                // fetch하여 각각 행으로 생성
                while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
?>
                <tr>
                    <td><?=$row['ISBN']?></td>
                    <td><?= $row['DELAYDAY'] ?></td>
                    <td><?= $row['CNT'] ?></td>
                </tr>
                <?php
}
?>
            </tbody>
        </table>

    </div>
</body>

</html>