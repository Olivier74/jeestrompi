# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import logging
import string
import sys
import os
import time
import datetime
import traceback
import re
import signal
from optparse import OptionParser
from os.path import join
import json
import argparse
import serial

try:
	from jeedom.jeedom import *
except ImportError:
	print("Error: importing module jeedom.jeedom")
	sys.exit(1)

def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	if not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
		message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
		if message['apikey'] != _apikey:
			logging.error("Invalid apikey from socket: %s", message)
			return
		try:
			print ('read')
		except Exception as e:
			logging.error('Send command to demon error: %s' ,e)

def listen():
	jeedom_socket.open()
	try:
		while 1:
			time.sleep(0.5)
			read_socket()
            x=ser.readline()
            y = x.decode(encoding='UTF-8',errors='strict')
            logging.debug("Signal %i caught, exiting...", y)
            
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting...", int(signum))
	shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file %s", _pidfile)
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	try:
		jeedom_serial.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = "error"
_socket_port = 55009
_socket_host = 'localhost'
_device = 'auto'
_pidfile = '/tmp/demond.pid'
_apikey = ''
_callback = ''
_cycle = 0.3

parser = argparse.ArgumentParser(
    description='Desmond Daemon for Jeedom plugin')
parser.add_argument("--serialport", help="serial port", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--serialbaud", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--socketport", help="Port for jeestrompi server", type=str)
args = parser.parse_args()

if args.serialport:
	_serialport = args.serialport
if args.loglevel:
    _log_level = args.loglevel
if args.callback:
    _callback = args.callback
if args.apikey:
    _apikey = args.apikey
if args.pid:
    _pidfile = args.pid
if args.cycle:
    _cycle = float(args.cycle)
if args.serialbaud:
    _serialbaud = int(args.serialbaud)
if args.socketport:
	_socketport = args.socketport


_socket_port = int(_socketport)

jeedom_utils.set_log_level(_log_level)

logging.info('Start demond')
logging.info('Log level: %s', _log_level)
logging.info('Socket port: %s', _socket_port)
logging.info('Socket host: %s', _socket_host)
logging.info('PID file: %s', _pidfile)
logging.info('Apikey: %s', _apikey)
logging.info('serialport: %s', _serialport)

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

ser = serial.Serial(
 port='/dev/serial0',
 baudrate = 38400,
 parity=serial.PARITY_NONE,
 stopbits=serial.STOPBITS_ONE,
 bytesize=serial.EIGHTBITS,
 timeout=1
)
counter=0


try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception as e:
	logging.error('Fatal error: %s', e)
	logging.info(traceback.format_exc())
	shutdown()
