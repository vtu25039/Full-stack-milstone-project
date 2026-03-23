-- ============================================================
--  Automated Quiz Engine - Database Schema
-- ============================================================
CREATE DATABASE IF NOT EXISTS quizcert_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quizcert_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)  NOT NULL,
    email       VARCHAR(180)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('user','admin') DEFAULT 'user',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes
CREATE TABLE IF NOT EXISTS quizzes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200)  NOT NULL,
    description  TEXT,
    time_limit   INT DEFAULT 600  COMMENT 'seconds',
    pass_score   INT DEFAULT 60   COMMENT 'percent',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Questions
CREATE TABLE IF NOT EXISTS questions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id        INT NOT NULL,
    question_text  TEXT NOT NULL,
    question_type  ENUM('mcq','truefalse') DEFAULT 'mcq',
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Options (for MCQ & True/False)
CREATE TABLE IF NOT EXISTS options (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    question_id  INT NOT NULL,
    option_text  VARCHAR(500) NOT NULL,
    is_correct   TINYINT(1) DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Attempts
CREATE TABLE IF NOT EXISTS attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    quiz_id      INT NOT NULL,
    score        DECIMAL(5,2) DEFAULT 0,
    passed       TINYINT(1) DEFAULT 0,
    started_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Attempt Answers
CREATE TABLE IF NOT EXISTS attempt_answers (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id        INT NOT NULL,
    question_id       INT NOT NULL,
    selected_option_id INT,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Certificates
CREATE TABLE IF NOT EXISTS certificates (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL UNIQUE,
    user_id    INT NOT NULL,
    cert_uuid  VARCHAR(64) NOT NULL UNIQUE,
    issued_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
--  Sample Data
-- ============================================================

-- Admin user  (password: Admin@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Administrator', 'admin@quizcert.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample Quizzes
INSERT INTO quizzes (title, description, time_limit, pass_score) VALUES
('Web Development Fundamentals',
 'Test your knowledge of HTML, CSS, JavaScript and PHP basics. A must-pass for aspiring web developers.',
 600, 60),
('Python Programming Essentials',
 'Covers Python syntax, data structures, OOP, and common libraries. Suitable for beginners and intermediates.',
 900, 65),
('Database Design & SQL',
 'Evaluate understanding of relational databases, ER models, normalization, and SQL queries.',
 720, 70),
('Software Engineering Principles',
 'Covers SDLC, design patterns, Agile methodologies, and software testing fundamentals.',
 600, 60);

-- ---- Quiz 1: Web Development (10 questions) ----
INSERT INTO questions (quiz_id, question_text, question_type) VALUES
(1, 'Which HTML tag is used to define an internal style sheet?',             'mcq'),
(1, 'What does CSS stand for?',                                              'mcq'),
(1, 'JavaScript is a client-side scripting language.',                       'truefalse'),
(1, 'Which method is used to add an element at the end of a JavaScript array?', 'mcq'),
(1, 'What does PHP stand for?',                                              'mcq'),
(1, 'CSS Flexbox is used for two-dimensional layouts.',                      'truefalse'),
(1, 'Which property is used to change the background color in CSS?',         'mcq'),
(1, 'In HTML, which attribute specifies an alternate text for an image?',    'mcq'),
(1, 'PHP runs on the client side.',                                          'truefalse'),
(1, 'Which SQL statement is used to extract data from a database?',          'mcq');

-- Q1 options
INSERT INTO options (question_id, option_text, is_correct) VALUES
(1,'<style>',1),(1,'<css>',0),(1,'<script>',0),(1,'<link>',0),
(2,'Creative Style Sheets',0),(2,'Cascading Style Sheets',1),(2,'Computer Style Sheets',0),(2,'Colorful Style Sheets',0),
(3,'True',1),(3,'False',0),
(4,'push()',1),(4,'pop()',0),(4,'shift()',0),(4,'unshift()',0),
(5,'PHP Hypertext Preprocessor',1),(5,'Personal Home Page',0),(5,'Preprocessed Hypertext Pages',0),(5,'PHP: Hypertext Protocol',0),
(6,'True',0),(6,'False',1),
(7,'background-color',1),(7,'color',0),(7,'bg-color',0),(7,'background-style',0),
(8,'alt',1),(8,'title',0),(8,'src',0),(8,'href',0),
(9,'True',0),(9,'False',1),
(10,'SELECT',1),(10,'GET',0),(10,'OPEN',0),(10,'EXTRACT',0);

-- ---- Quiz 2: Python (8 questions) ----
INSERT INTO questions (quiz_id, question_text, question_type) VALUES
(2, 'Which keyword is used to define a function in Python?',       'mcq'),
(2, 'Python is case-sensitive.',                                   'truefalse'),
(2, 'What is the output of type(3.14) in Python?',                 'mcq'),
(2, 'Which of these is a mutable data type in Python?',            'mcq'),
(2, 'Python supports multiple inheritance.',                       'truefalse'),
(2, 'What does len() function return?',                            'mcq'),
(2, 'Which symbol is used for single-line comments in Python?',    'mcq'),
(2, 'Indentation is optional in Python.',                          'truefalse');

INSERT INTO options (question_id, option_text, is_correct) VALUES
(11,'def',1),(11,'function',0),(11,'fun',0),(11,'define',0),
(12,'True',1),(12,'False',0),
(13,"<class 'float'>",1),(13,"<class 'double'>",0),(13,"<class 'int'>",0),(13,"<class 'decimal'>",0),
(14,'list',1),(14,'tuple',0),(14,'string',0),(14,'integer',0),
(15,'True',1),(15,'False',0),
(16,'Length of an object',1),(16,'Last element',0),(16,'Line count',0),(16,'Size in bytes',0),
(17,'#',1),(17,'//',0),(17,'/*',0),(17,'--',0),
(18,'True',0),(18,'False',1);

-- ---- Quiz 3: Database (8 questions) ----
INSERT INTO questions (quiz_id, question_text, question_type) VALUES
(3, 'What does SQL stand for?',                                           'mcq'),
(3, 'A PRIMARY KEY can contain NULL values.',                             'truefalse'),
(3, 'Which SQL clause is used to filter records?',                        'mcq'),
(3, 'What is normalization in databases?',                                'mcq'),
(3, 'A FOREIGN KEY refers to the PRIMARY KEY in another table.',          'truefalse'),
(3, 'Which JOIN returns all rows from both tables?',                      'mcq'),
(3, 'Which command removes all records from a table without deleting it?','mcq'),
(3, 'An index improves query performance.',                               'truefalse');

INSERT INTO options (question_id, option_text, is_correct) VALUES
(19,'Structured Query Language',1),(19,'Simple Query Language',0),(19,'Sequential Query Language',0),(19,'Standard Query List',0),
(20,'True',0),(20,'False',1),
(21,'WHERE',1),(21,'HAVING',0),(21,'FROM',0),(21,'ORDER BY',0),
(22,'Process of organizing data to reduce redundancy',1),(22,'Backing up the database',0),(22,'Adding indexes',0),(22,'Encrypting data',0),
(23,'True',1),(23,'False',0),
(24,'FULL OUTER JOIN',1),(24,'INNER JOIN',0),(24,'LEFT JOIN',0),(24,'RIGHT JOIN',0),
(25,'TRUNCATE',1),(25,'DELETE',0),(25,'DROP',0),(25,'REMOVE',0),
(26,'True',1),(26,'False',0);

-- ---- Quiz 4: Software Engineering (8 questions) ----
INSERT INTO questions (quiz_id, question_text, question_type) VALUES
(4, 'What does SDLC stand for?',                                           'mcq'),
(4, 'Agile methodology follows a linear sequential approach.',             'truefalse'),
(4, 'Which design pattern ensures only one instance of a class?',          'mcq'),
(4, 'What is a use case diagram used for?',                                'mcq'),
(4, 'Unit testing is performed by end users.',                             'truefalse'),
(4, 'Which SDLC model is best for projects with changing requirements?',   'mcq'),
(4, 'What does DRY stand for in software engineering?',                    'mcq'),
(4, 'Code review improves software quality.',                              'truefalse');

INSERT INTO options (question_id, option_text, is_correct) VALUES
(27,'Software Development Life Cycle',1),(27,'System Design Life Cycle',0),(27,'Software Deployment Life Cycle',0),(27,'Standard Development Language Cycle',0),
(28,'True',0),(28,'False',1),
(29,'Singleton',1),(29,'Observer',0),(29,'Factory',0),(29,'Decorator',0),
(30,'Model system behavior from user perspective',1),(30,'Show database structure',0),(30,'Display code flow',0),(30,'Plan deployment',0),
(31,'True',0),(31,'False',1),
(32,'Agile',1),(32,'Waterfall',0),(32,'Spiral',0),(32,'V-Model',0),
(33,"Don't Repeat Yourself",1),(33,'Dynamic Runtime Yield',0),(33,'Data Reduction Yield',0),(33,'Design Redundancy Yield',0),
(34,'True',1),(34,'False',0);
