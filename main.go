package main

import (
	"fmt"
	"log"
	"os"
	"os/exec"
	"sync"
	"syscall"
	"time"
)

var wg = sync.WaitGroup{}

func main() {
	fmt.Println("start test")
	start := time.Now()
	// 判断文件是否存在，如果存在需要在开始测试前清除
	fileName := "./test.log"
	file, err := os.Create(fileName)
	if err != nil {
		log.Fatal(err)
	}
	err = file.Close()
	if err != nil {
		log.Fatal(err)
	}
	for i := 0; i < 20; i++ {
		wg.Add(1)
		//go runPhp("put")
		//go runPhp("write")
		//go runPhp("log")
		go goWriteFile("./test.log")
	}
	wg.Wait()
	cost := time.Since(start)
	fmt.Println("end test. cost:", cost)
}

func runPhp(t string) {
	cmd := exec.Command("php", "testWriteFile.php", t)
	start := time.Now()
	_, err := cmd.Output()
	if err != nil {
		panic(err)
	}
	cost := time.Since(start)
	fmt.Println(t, cost)
	wg.Done()
}

func goWriteFile(fileName string) {
	start := time.Now()
	file, err := os.OpenFile(fileName, syscall.O_RDWR|syscall.O_APPEND, 0666)
	if err != nil {
		log.Fatal(err)
	}
	defer file.Close()
	if err != nil {
		log.Fatal(err)
	}
	lockFile(file)

	//jsonMap := make(map[string]interface{}, 0)
	//jsonMap["time"] = time.Now()
	//jsonMap["type"] = "local"
	//jsonMap["rand"] = rand.Int()
	//str, err := json.Marshal(jsonMap)
	//if err != nil {
	//	log.Fatal(err)
	//}
	msg := ""
	for i := 0; i < 1024*16; i++ {
		msg += fmt.Sprintf("%d", i%10)
	}

	file.WriteString(msg + "\n")
	unlockFile(file)
	cost := time.Since(start)
	fmt.Println("local", cost)
	wg.Done()
}

func lockFile(file *os.File) {
	err := syscall.Flock(int(file.Fd()), syscall.LOCK_EX)
	if err != nil {
		log.Fatal(err)
	}
}

func unlockFile(file *os.File) {
	err := syscall.Flock(int(file.Fd()), syscall.LOCK_UN)
	if err != nil {
		log.Fatalf("cannot flock directory %s - %s", file.Name(), err)
	}
}
