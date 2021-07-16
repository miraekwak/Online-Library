<?php
//디비 연결
$tns = "
    (DESCRIPTION=
        (ADDRESS_LIST= (ADDRESS=(PROTOCOL=TCP)(HOST=LOCALHOST)(PORT=1521)))
        (CONNECT_DATA= (SERVICE_NAME=XE))
    )
";
$dsn = "oci:dbname=".$tns.";charset=utf8";
$username = 'c##madang';
$password = 'madang';
$dbh = new PDO($dsn, $username, $password);

// 세션에 저장된 id와 get방식으로 받은 도서번호 저장
session_start();
$id = $_SESSION["id"];
$isbn = $_GET["isbn"];

// 도서에 대해 기본 정보 추출
$stmt = $dbh->prepare('SELECT TITLE, CNO, DATERENTED FROM EBOOK WHERE ISBN = :isbn');
$stmt->bindParam(':isbn', $isbn);
$stmt->execute();
$row = $stmt -> fetch(PDO::FETCH_ASSOC);
$title = $row['TITLE'];
$cno = $row['CNO'];
$daterented = $row['DATERENTED'];
$datereturned = date('Y/m/d');

// 대출, 예약, 반납, 기간연장, 예약 취소의 모드에 대해 각각 처리 
switch($_GET['mode']){
    //대출
    case 'rental':
        // 고객이 대출한 책의 수를 확인하고 2권 이하라면 대출 표시를 위해 EBOOK테이블에 업데이트
        // 대출일은 현재 date 반납일은 기한이 10일로 설정
        // 완료 후 이전 서버 booklist로 돌아감
        $stmt = $dbh->prepare("SELECT COUNT(*) as CNT FROM EBOOK WHERE CNO = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt -> fetch(PDO::FETCH_ASSOC);
        if($row['CNT'] >= 3){
            echo "<script>location.href='booklist.php'; alert('대출은 최대 3권만 가능합니다')</script>";
        }else{
            $stmt = $dbh->prepare('UPDATE EBOOK SET CNO = :id, EXTTIMES=0, 
                                    DATERENTED=SYSDATE, DATEDUE=SYSDATE+10 
                                    WHERE ISBN = :isbn');
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            echo "<script>location.href='booklist.php'; alert('[$title] 도서가 대출되었습니다.')</script>";
        }
        break;
    //예약
    case 'reserve':
        // 예약한 도서의 수 확인 후 2권 이하면 reserve 테이블에 데이터 삽입
        // 완료 후 이전 서버 booklist로 돌아감
        $stmt = $dbh->prepare("SELECT COUNT(*) as CNT FROM RESERVE WHERE CNO = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt -> fetch(PDO::FETCH_ASSOC);
        if($row['CNT'] >= 3){
            echo "<script>location.href='booklist.php'; alert('예약은 최대 3권만 가능합니다')</script>";
        }else{
            $stmt = $dbh->prepare('INSERT INTO RESERVE(ISBN, CNO, DATETIME) VALUES(:isbn, :id, SYSDATE)');
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            echo "<script>location.href='booklist.php'; alert('[$title] 도서가 예약되었습니다.')</script>";
        }
        break;
    //반납
    case 'return':
        // 이전 대여기록 테이블에 반납된 정보 삽입
        $stmt = $dbh -> prepare('INSERT INTO PREVIOUSRENTAL(ISBN, DATERENTED, DATERETURNED, CNO) 
                                VALUES(:isbn, :daterented, :datereturned, :id)');
        $stmt -> execute(array($isbn, $daterented, $datereturned, $cno));
        // 반납된 도서에 대해 예약자가 존재하는지 확인 후 존재하면 메일 전송을 위한 mailer.php로 이동
        // 예약자가 없다면 이전 서버 rentallist로 이동
        $stmt = $dbh->prepare('SELECT CNO 
                                FROM (SELECT CNO 
                                        FROM RESINFO 
                                        WHERE ISBN = :isbn 
                                        ORDER BY DATETIME) T 
                                WHERE ROWNUM = 1');
        $stmt->bindParam(':isbn', $isbn);
        $stmt->execute();
        $row = $stmt -> fetch(PDO::FETCH_ASSOC);
        if($row['CNO'] != null){
            // 반납 처리를 위해 Ebook 테이블의 대출 정보 업데이트
            $stmt = $dbh->prepare('UPDATE EBOOK SET CNO = :cno, EXTTIMES = NULL, DATERENTED = NULL, DATEDUE = sysdate 
                                    WHERE ISBN = :isbn');
            $stmt->bindParam(':cno', $row['CNO']);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            $_SESSION['CNO'] = $row['CNO'];
            echo "<script>alert('[$title] 도서가 반납되었습니다.'); location.href='mailer.php?isbn=$isbn';</script>";
        }else{
            // 반납 처리를 위해 Ebook 테이블의 대출 정보 업데이트
            $stmt = $dbh->prepare('UPDATE EBOOK SET CNO = NULL, EXTTIMES = NULL, DATERENTED = NULL, DATEDUE = NULL 
                                    WHERE ISBN = :isbn');
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            echo "<script>location.href='rentallist.php'; alert('[$title] 도서가 반납되었습니다.')</script>";
        }
        break;
    //기간연장
    case 'extension':
        // 예약자가 있을 경우 기간 연장이 불가하기 때문에 예약자 수 확인
        $stmt = $dbh->prepare("SELECT COUNT(*) as CNT FROM RESERVE WHERE isbn = :isbn");
        $stmt->bindParam(':isbn', $isbn);
        $stmt->execute();
        $row = $stmt -> fetch(PDO::FETCH_ASSOC);
        // 예약자가 있다면 연장 불가로 이전 페이지로 돌아감
        if($row['CNT']>0){
            echo "<script>location.href='rentallist.php'; alert('예약된 도서로 기간 연장이 불가능합니다.');</script>";
            break;
        }
        //예약자가 없다면 연장 횟수 확인
        $stmt = $dbh->prepare("SELECT EXTTIMES FROM EBOOK WHERE ISBN = :isbn");
        $stmt->bindParam(':isbn', $isbn);
        $stmt->execute();
        $row = $stmt -> fetch(PDO::FETCH_ASSOC);

        // 연장 횟수가 2회 이상이면 연장 불가로 이전페이지로 돌아감
        if($row['EXTTIMES'] >= 2){
            echo "<script>location.href='rentallist.php'; alert('기간 연장은 최대 2번만 가능합니다');</script>";
        }else{//Ebook테이블의 연장 횟수와 반납 기한을 업데이트한 후 이전페이지로 돌아감
            $stmt = $dbh->prepare('UPDATE EBOOK SET EXTTIMES=EXTTIMES+1, DATEDUE=DATEDUE+10 WHERE ISBN = :isbn');
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            echo "<script>location.href='rentallist.php'; alert('[$title] 도서가 연장되었습니다.')</script>";
        }
        break;
    //예약 취소
    case 'cancel':
        // 예약 테이블에서 해당 예약을 삭제한 후 이전 페이지로 이동
        $stmt = $dbh->prepare('DELETE FROM RESERVE WHERE ISBN = :isbn AND CNO = :id');
        $stmt->bindParam(':isbn', $isbn);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        echo "<script>location.href='reservelist.php'; alert('[$title] 도서의 예약이 취소되었습니다.')</script>";
        break;
    case 'rentalNcancel':
        $stmt = $dbh->prepare("SELECT COUNT(*) as CNT FROM EBOOK WHERE CNO = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt -> fetch(PDO::FETCH_ASSOC);
        if($row['CNT'] >= 3){
            echo "<script>location.href='booklist.php'; alert('대출은 최대 3권만 가능합니다')</script>";
        }else{
            // 예약 삭제
            $stmt = $dbh->prepare('DELETE FROM RESERVE WHERE ISBN = :isbn AND CNO = :id');
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // 대출
            $stmt = $dbh->prepare('UPDATE EBOOK SET CNO = :id, EXTTIMES=0, 
                                    DATERENTED=SYSDATE, DATEDUE=SYSDATE+10 
                                    WHERE ISBN = :isbn');
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->execute();
            echo "<script>location.href='booklist.php'; alert('[$title] 도서가 대출되었습니다.')</script>";
        }
        break;
}
?>