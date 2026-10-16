<!DOCTYPE html>
<html>
<head>
    <!-- REPLACE 'Your Name Here' WITH YOUR ACTUAL NAME TO PASS THE AUTOGRADER -->
    <title>Ziyad Usman Khan - Guessing Game for Charles Severance 81ae5ef3</title>
</head>
<body>
<h1>Welcome to my guessing game</h1>
<p>
<?php
// The autograder will tell you what the correct number should be.
// Change this value to match what your specific autograder requires.
$correct_answer = 77; 

if ( ! isset($_GET['guess']) ) {
    echo "Missing guess parameter";
} else if ( strlen($_GET['guess']) < 1 ) {
    echo "Your guess is too short";
} else if ( ! is_numeric($_GET['guess']) ) {
    echo "Your guess is not a number";
} else if ( $_GET['guess'] < $correct_answer ) {
    echo "Your guess is too low";
} else if ( $_GET['guess'] > $correct_answer ) {
    echo "Your guess is too high";
} else {
    echo "Congratulations - You are right";
}
?>
</p>
</body>
</html>
