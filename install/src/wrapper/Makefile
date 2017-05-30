CC=gcc
CFLAGS= -g -Wall
LDFLAGS=
SRCS=setuid_wrapper.c
OBJS=setuid_wrapper.o

setuid_wrapper: $(OBJS)
	$(CC) $(CFLAGS) $(LDFLAGS) $(OBJS)  -o setuid_wrapper

clean:
	-rm *.o setuid_wrapper.c setuid_wrapper

.c.o:
	$(CC) $(CFLAGS) -c $<

install:
	cp setuid_wrapper ../../../bin/
	chmod u+s ../../../bin/setuid_wrapper

