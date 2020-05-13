# 功能
`main.go`文件里有多协程调用`testPHPWrite.php`的方法。也有利用go内置函数写log的方法
`testPHPWrite.php` 里面分别实现了三种写log方法
1. php内置方法file_put_content
2. php内置方法fwrite
3. 使用GitHub上的php log组件`monolog/monolog`

另外提供了fork方法，运行多个子进程来写log。

php文件的调用方法如下
```bash
# 使用fwrite 写一行日志
php testWriteFile.php write 

# 使用monolog 写一行日志
php testWriteFile.php log 

# 使用file_put_content 写一行日志
php testWriteFile.php put 

# 使用fork方法，生成150个子进程执行
php testWriteFile.php fork  [write | log | put]
```