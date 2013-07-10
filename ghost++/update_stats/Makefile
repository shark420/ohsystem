SHELL = /bin/sh
SYSTEM = $(shell uname)
C++ = g++
DFLAGS =
OFLAGS = -O3
LFLAGS = -lmysqlclient
CFLAGS =

ifeq ($(SYSTEM),Darwin)
DFLAGS += -D__APPLE__
OFLAGS += -flat_namespace
endif

ifeq ($(SYSTEM),FreeBSD)
DFLAGS += -D__FREEBSD__
endif

ifeq ($(SYSTEM),SunOS)
DFLAGS += -D__SOLARIS__
LFLAGS += -lresolv -lsocket -lnsl
endif

CFLAGS += $(OFLAGS) $(DFLAGS) -I. -I../ghost/

GHOSTOBJS = config.o
OBJS = update_stats.o
PROGS = ./stats

all: $(GHOSTOBJS) $(OBJS) $(PROGS)

./stats: $(GHOSTOBJS) $(OBJS) $(COBJS)
	$(C++) -o ./stats $(GHOSTOBJS) $(OBJS) $(LFLAGS)

clean:
	rm -f $(GHOSTOBJS) $(OBJS) $(PROGS)

$(GHOSTOBJS): %.o: ../ghost/%.cpp
	$(C++) -o $@ $(CFLAGS) -c $<

$(OBJS): %.o: %.cpp
	$(C++) -o $@ $(CFLAGS) -c $<

./stats: $(GHOSTOBJS) $(OBJS)

all: $(PROGS)

config.o: ../ghost/ghost.h ../ghost/config.h
update_stats.o: ../ghost/config.h 
