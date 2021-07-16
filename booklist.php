<?php
session_start();
// 자동 반납을 위한 코드로 날짜가 달라질 때 마다 실행됨
// 도서 목록이 보여지는 곳에서만 자동반납 프로세스를 확인함
// session에 저장된 time변수의 date값을 보고 실행할지 말지 결정
// autoreturn.php를 실행하여 자동 반납을 실행함
// 밑의 주석은 데모를 위한 코드로 시간을 변경하여 자동 반납 확인
// $time = date('Y/m/d', strtotime('-1 day'));
$check = $_SESSION['check'] ?? 'no';
$time = $_SESSION['time'] ?? date('Y/m/d', strtotime('-1 day'));
if(strtotime($time) < strtotime(date('Y/m/d'))){
    $check = 'no';
}
if($check == 'no'){
    echo "<script>location.href='autoreturn.php';</script>";
}

// 디비 연결을 위한 코드
$tns = "
(DESCRIPTION=
     (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))
     (CONNECT_DATA= (SERVICE_NAME=XE))   
 )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';

// 검색 시 form을 통해 post 방식으로 전달되는 input값을 각 변수에 저장함
$id = $_SESSION["id"];
$searchTitle = $_POST['searchTitle'] ?? '';
$searchAuthor = $_POST['searchAuthor'] ?? '';
$searchPublisher = $_POST['searchPublisher'] ?? '';
$searchYear1 = $_POST['searchYear1'] ?? '1900';
$searchYear2 = $_POST['searchYear2'] ?? '2021';
$radio = $_POST["radio"] ?? '';

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
    <title>BOOK LIST</title>
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
        <a href="booklist.php" class="navbar-brand"><?=$row["NAME"]?> 님</a>
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
            <h2>Book List</h2>
        </div>
        <hr>
        <!--
            입력받은 검색어를 post방식으로 현재 페이지에 전송
            체크박스를 통해 검색할 조건들만을 체크
            조건들 간의 and, or, not 연산을 라디오버튼으로 선택
        -->
        <form class="row searchinput" action="booklist.php" method="post">
            <div class="col-4">
                <input type="checkbox" class="chk" name="chk_title">
                <label for="title">제목</label>
                <input type="text" class=" search" id="searchTitle" name="searchTitle" disabled="true"
                    value="<?= $searchTitle?>">
            </div>
            <div class="col-4">
                <input type="checkbox" class="chk" name="chk_author">
                <label for="author">저자</label>
                <input type="text" class="search" id="searchAuthor" name="searchAuthor" disabled="true"
                    value="<?= $searchAuthor?>">
            </div>
            <div class="col-4">
                <input type="checkbox" class="chk" name="chk_publisher">
                <label for="publisher">출판사</label>
                <input type="text" class="search" id="searchPublisher" name="searchPublisher" disabled="true"
                    value="<?= $searchPublisher?>">
            </div>
            <div class="col-7">
                <input type="checkbox" class="chk" name="chk_year">
                <label for="year">발행년도</label>
                <input type="text" class="search" id="year" name="searchYear1" disabled="true"
                    value="<?= $searchYear1?>"> ~
                <input type="text" class="search" id="year" name="searchYear2" disabled="true"
                    value="<?= $searchYear2?>">
            </div>
            <div class="col-3">
                <input type="radio" class="radio" name="radio" id="and" value="and" checked>
                <label for="and">AND</label>
                <input type="radio" class="radio" name="radio" id="or" value="or">
                <label for="or">OR</label>
                <input type="radio" class="radio" name="radio" id="not" value="not">
                <label for="not">NOT</label>
            </div>
            <div class="col-auto text-end">
                <button type="submit" class="btn btn-primary sm">검색</button>
            </div>
        </form>
        <hr>
        <!--도서 목록이 나타날 테이블-->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>제목</th>
                    <th>저자</th>
                    <th>출판사</th>
                    <th>발행년도</th>
                    <th>대출/예약</th>
                </tr>
            </thead>
            <tbody>
                <!--
                    and or not 연산에 따라 각각 다른 오라클 구문이 필요
                    경우에 따라서 각 경우에 맞는 구문을 prepare
                    Authors 테이블과 Ebook 테이블을 join 하여 도서 목록 뽑아냄
                    (현재 customer가 대출한 도서 제외 & 공동 저자 합침)
                    현재 customer가 대여한 도서를 제외하기 위해 reserve 테이블의 현재 customer가 빌린 행을 not exists 
                -->
                <?php
if($radio == "not"){
    // 입력으로 받아지지 않는 값은 검색 시 제외하기 위해 특수한 값을 저장함
    $searchTitle = isset($_POST["chk_title"]) ? $searchTitle : '@';
    $searchAuthor = isset($_POST["chk_author"]) ? $searchAuthor : '@';
    $searchPublisher = isset($_POST["chk_publisher"]) ? $searchPublisher : '@';
    $searchYear1 = isset($_POST["chk_year"]) ? $searchYear1 : '1000';
    $searchYear2 = isset($_POST["chk_year"]) ? $searchYear2 : '1100';
    $stmt = $conn -> prepare("
    SELECT T.ISBN, T.TITLE, T.AUTHOR, T.PUBLISHER, T.ISRENTAL, T.YEAR
    FROM(SELECT E.ISBN as ISBN, E.TITLE AS TITLE, E.PUBLISHER AS PUBLISHER, 
                EXTRACT(YEAR FROM E.YEAR) as YEAR, E.CNO AS CNO, NVL(E.CNO, 0) AS ISRENTAL,
                LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
        FROM EBOOK E, AUTHORS A
        WHERE E.ISBN = A.ISBN
        GROUP BY E.ISBN, E.TITLE, E.PUBLISHER, E.CNO, E.YEAR, E.EXTTIMES
        HAVING E.CNO != :id OR E.EXTTIMES IS NULL) T
    WHERE NOT LOWER(T.TITLE) LIKE '%' || :searchTitle || '%'
    and NOT LOWER(T.AUTHOR) LIKE '%' || :searchAuthor || '%'
    and NOT LOWER(T.PUBLISHER) LIKE '%' || :searchPublisher || '%'
    and NOT T.YEAR BETWEEN :searchYear1 and :searchYear2
    and NOT EXISTS(SELECT CNO, ISBN FROM RESERVE WHERE CNO = :id AND ISBN = T.ISBN)
    ORDER BY 1
    ");
}
else if($radio == "or"){
    // 입력으로 받아지지 않는 값은 검색 시 제외하기 위해 특수한 값을 저장함
    $searchTitle = isset($_POST["chk_title"]) ? $searchTitle : '@';
    $searchAuthor = isset($_POST["chk_author"]) ? $searchAuthor : '@';
    $searchPublisher = isset($_POST["chk_publisher"]) ? $searchPublisher : '@';
    $searchYear1 = isset($_POST["chk_year"]) ? $searchYear1 : '1000';
    $searchYear2 = isset($_POST["chk_year"]) ? $searchYear2 : '1100';
    $stmt = $conn -> prepare("
    SELECT T.ISBN, T.TITLE, T.AUTHOR, T.PUBLISHER, T.ISRENTAL, T.YEAR
    FROM(SELECT E.ISBN as ISBN, E.TITLE AS TITLE, E.PUBLISHER AS PUBLISHER, 
                EXTRACT(YEAR FROM E.YEAR) as YEAR, E.CNO AS CNO, NVL(E.CNO, 0) AS ISRENTAL,
                LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
        FROM EBOOK E, AUTHORS A
        WHERE E.ISBN = A.ISBN
        GROUP BY E.ISBN, E.TITLE, E.PUBLISHER, E.CNO, E.YEAR, E.EXTTIMES
        HAVING E.CNO != :id OR E.EXTTIMES IS NULL) T
    WHERE (LOWER(T.TITLE) LIKE '%' || :searchTitle || '%'
    or LOWER(T.AUTHOR) LIKE '%' || :searchAuthor || '%'
    or LOWER(T.PUBLISHER) LIKE '%' || :searchPublisher || '%'
    or T.YEAR BETWEEN :searchYear1 and :searchYear2)
    and NOT EXISTS(SELECT CNO, ISBN FROM RESERVE WHERE CNO = :id AND ISBN = T.ISBN)
    ORDER BY 1
    ");
}else{
    $stmt = $conn -> prepare("
    SELECT T.ISBN, T.TITLE, T.AUTHOR, T.PUBLISHER, T.ISRENTAL, T.YEAR
    FROM(SELECT E.ISBN as ISBN, E.TITLE AS TITLE, E.PUBLISHER AS PUBLISHER, 
                EXTRACT(YEAR FROM E.YEAR) as YEAR, E.CNO AS CNO, NVL(E.CNO, 0) AS ISRENTAL,
                LISTAGG(A.AUTHOR, ', ') WITHIN GROUP(ORDER BY A.AUTHOR) AS AUTHOR
        FROM EBOOK E, AUTHORS A
        WHERE E.ISBN = A.ISBN
        GROUP BY E.ISBN, E.TITLE, E.PUBLISHER, E.CNO, E.YEAR, E.EXTTIMES
        HAVING E.CNO != :id OR E.EXTTIMES IS NULL) T
    WHERE LOWER(T.TITLE) LIKE '%' || :searchTitle || '%'
    and LOWER(T.AUTHOR) LIKE '%' || :searchAuthor || '%'
    and LOWER(T.PUBLISHER) LIKE '%' || :searchPublisher || '%'
    and T.YEAR BETWEEN :searchYear1 and :searchYear2
    and NOT EXISTS(SELECT CNO, ISBN FROM RESERVE WHERE CNO = :id AND ISBN = T.ISBN)
    ORDER BY 1
");
}
// 검색 조건들을 집어넣어 execute
$stmt -> execute(array($id,$searchTitle,$searchAuthor,$searchPublisher,$searchYear1,$searchYear2,$id));

// 현재 customer에 의해 대출, 예약된 도서를 제외한 도서목록을 fetch
while ($row = $stmt -> fetch(PDO::FETCH_ASSOC)) {
?>
                <tr>
                    <td><?=$row['ISBN']?></td>
                    <!--도서명 클릭 시 도서 상세 정보를 보여주기 위해 링크 설정, get방식으로 도서번호 전송-->
                    <td>
                        <a href="bookview.php?isbn=<?=$row['ISBN']?>"><?=$row['TITLE']?></a>
                    </td>
                    <td><?= $row['AUTHOR'] ?></td>
                    <td><?= $row['PUBLISHER'] ?></td>
                    <td><?= $row['YEAR'] ?></td>
                    <td>
                        <?php
                    $isbn = $row['ISBN'];
                    $btn_mode = ($row["ISRENTAL"] == 0) ? "btn-success" : "btn-warning";
                    $mode = ($row["ISRENTAL"] == 0) ? "rental" : "reserve";
                    $mode_k = ($row["ISRENTAL"] == 0 )? "대출" : "예약";
                    // ISRENTAL은 디비에서 select한 값으로 CNO가 존재하면 해당 CNO값을 가지고 없으면 0값을 가짐
                    // 0인경우 예약이 있지 않은 도서로 mode를 rental로, 이외의 경우 reserve로 설정
                    // 도서번호와 모드를 get방식으로 process.php에 넘겨 처리
                    if($row["ISRENTAL"] == 0){
                        echo "<a class='btn btn-success' href='process.php?isbn=$isbn&mode=rental'>대출</a>";
                    }else{
                        echo "<a class='btn btn-warning' href='process.php?isbn=$isbn&mode=reserve'>예약</a>";
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
<script src="booklist.js"></script>

</html>