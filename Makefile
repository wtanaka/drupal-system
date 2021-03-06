all: system.patch.bz2 system.tar.bz2

clean:
	find . -name "*~" -print -exec rm \{\} \;
	rm -f system.patch system.patch.bz2 system.tar system.tar.bz2

system.tar: modules/system/*.inc.php modules/system/system.module
	tar cvf "$@" $^

system.patch: modules/system/*
	git diff --no-prefix origin/vendor6 modules/system > "$@"

%.bz2: %
	bzip2 -9 -c $^ > "$@"
