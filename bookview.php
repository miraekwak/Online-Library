<?php
//디비 연결
$tns = "
    (DESCRIPTION=
            (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=LOCALHOST)(PORT=1521)))
            (CONNECT_DATA= (SERVICE_NAME=XE))    
    )";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';

$isbn = $_GET['isbn'];
try {
   $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo("에러 내용: ".$e -> getMessage());
}

// authors와 ebook을 join하여 공동저자 처리 후 get방식으로 받은 isbn에 대한 정보 select
$stmt = $conn -> prepare("
SELECT E.ISBN AS ISBN, E.TITLE AS TITLE, T.AUTHOR AS AUTHOR, 
        E.PUBLISHER AS PUBLISHER, EXTRACT(YEAR FROM E.YEAR) AS YEAR
FROM(SELECT E.ISBN as ISBN, LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
    FROM EBOOK E, AUTHORS A
    WHERE E.ISBN = A.ISBN
    GROUP BY E.ISBN) T JOIN EBOOK E
ON T.ISBN = E.ISBN
WHERE E.ISBN = :isbn");

// 실행 후 fetch
$stmt -> execute(array($isbn));
$title = '';
$author = '';
$publisher = '';
$year = '';

if ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
    $title = $row['TITLE'];
    $author = $row['AUTHOR'];
    $publisher = $row['PUBLISHER'];
    $year = $row['YEAR'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"> 
    <!-- JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <link rel=”stylesheet” href=”http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css“>
    <link rel="stylesheet" href="bookview.css">
    <title>Book VIEW</title>
</head>

<body>
    <div class="container">
        <!--현재 페이지 제목 -->
        <div class="page-header">
            <h2 class="display-6">도서 정보</h2>
        </div>
        <hr>
        <!--테이블로 위에서 얻은 정보를 활용해서 보임-->
        <table class="table table-hover text-center">
            <tbody>
                <tr>
                    <td>ISBN</td>
                    <td><?= $isbn ?></td>
                </tr>
                <tr>
                    <td>제목</td> 
                    <td><?= $title ?></td>
                </tr>
                <tr>
                    <td>저자</td>
                    <td><?= $author ?></td>
                </tr>
                <tr>
                    <td>출판사</td>
                    <td><?= $publisher ?></td>
                </tr>
                <tr>
                    <td>발행년도</td>
                    <td><?= $year ?></td>
                </tr>
            </tbody>
        </table>
        <!--버튼 클릭 시 이전 페이지로 돌아가도록 함-->
        <button onclick="history.back()" class="btn btn-outline-light">뒤로가기</button>
<?php
}
?>
    </div>
</body>

</html>