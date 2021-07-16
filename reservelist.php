<?php
//디비연결
$tns = "
(DESCRIPTION=
     (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=LOCALHOST)(PORT=1521)))
     (CONNECT_DATA= (SERVICE_NAME=XE))   
 )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';

session_start();
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
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"> 
    <!-- JS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <link rel=”stylesheet” href=”http://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css“>
    <link rel="stylesheet" href="list.css">
    <title>RESERVE LIST</title>
</head>

<body>
    <!--로고와 로그아웃 div => 로그아웃 시 login.php로 이동-->
    <div class="jumbotron">
        <h1 class="text-center"><a href="main.php"> Online Library</a></h1>
        <a class="btn btn-sm btn-outline-dark float-right" href="login.php">LOGOUT</a>
    </div>

    <!--사용자의 id를 가지고 이름과 이메일을 가져와 nav에 띄움-->
    <?php
                $stmt = $conn -> prepare("SELECT NAME, EMAIL
                                            FROM CUSTOMER
                                            WHERE CNO = :id");
                $stmt -> execute(array($id));
                $row = $stmt -> fetch(PDO::FETCH_ASSOC);
            ?>
    
    <!--사용자의 이름과 각페이지로의 이동을 위한 nav-->
    <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
        <a href="#" class="navbar-brand"><?=$row["NAME"]?> 님</a>
        <!-- Toggle Button -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                <li class="nav-item"><a href="booklist.php" class="nav-link">도서 검색</a></li>
                <li class="nav-item"><a href="rentallist.php" class="nav-link">대출 조회</a></li>
                <li class="nav-item"><a href="reservelist.php" class="nav-link">예약 조회</a></li>
            </ul>
        </div>
    </nav>

    <!-- 페이지 내용 div-->
    <div class="container">
        <!-- 현재 페이지 제목-->
        <div class="page-header">
            <h2>Reserve List</h2>
        </div>
        <hr>
        <!--현재 예약 도서를 테이블로 나타냄-->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>제목</th>
                    <th>저자</th>
                    <th>예약일</th>
                    <th>예약 취소</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // authors와 ebook을 join하여 공동 저자 처리 후 reserve 테이블과 join하여 예약도서 select
$stmt = $conn -> prepare("
SELECT E.ISBN AS ISBN, E.TITLE AS TITLE, T.AUTHOR AS AUTHOR, R.DATETIME AS DATETIME, NVL2(E.DATERENTED, 0, 1) AS ISRENTAL
FROM(SELECT E.ISBN as ISBN, LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
    FROM EBOOK E, AUTHORS A
    WHERE E.ISBN = A.ISBN
    GROUP BY E.ISBN) T JOIN EBOOK E
ON T.ISBN = E.ISBN
JOIN RESERVE R
ON E.ISBN = R.ISBN
WHERE R.CNO = :id
ORDER BY 1");

//실행후 예약 도서 목록 fetch
$stmt -> execute(array($id));
while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
?>
                <tr>
                    <td><?= $row['ISBN'] ?></td>
                    <td>
                        <!--도서 제목 클릭 시 도서 정보를 보여주는 페이지로 이동한다.-->
                        <a href="bookview.php?isbn=<?=$row["ISBN"]?>"><?=$row['TITLE']?></a>
                    </td>
                    <td><?= $row['AUTHOR'] ?></td>
                    <td><?= $row['DATETIME'] ?></td>
                    <td>
                        <!--예약취소를 위한 버튼 생성, 클릭 시 isbn과 mode를 process.php로 전송-->
                        <?php
                        $isbn = $row["ISBN"];
                        if($row["ISRENTAL"] == 0){
                            echo "<a class='btn btn-info' href='process.php?isbn=$isbn&mode=cancel'>취소</a>";
                        }else{
                            echo "<a class='btn btn-success' href='process.php?isbn=$isbn&mode=rentalNcancel'>대출</a>";
                        }
                        
                        ?>
                    </td>
                </tr>
                <?php
}
?>
            </tbody>
        </table>

    </div>
</body>

</html>