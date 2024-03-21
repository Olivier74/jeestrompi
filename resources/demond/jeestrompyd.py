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
import time
import serial

try:
	from jeedom.jeedom import *
except ImportError:
	print("Error: importing module jeedom.jeedom")
	sys.exit(1)

def read_strompi():
	try:
		#x=ser.readline()
		x=ser.read(9999)
		y = x.decode(encoding='UTF-8',errors='strict')
		if y != "":
			#logging.debug('strompi receive: %s',y)
			if y.find('ShutdownRaspberryPi') > 0:
				logging.debug('mise a jour auto strompi sur message ShutdownRaspberryPi')
				time.sleep(1)
				read_strompi_status()
	except Exception as e:
		logging.error('Send command to demon error: %s' ,e)


def read_socket():
	global JEEDOM_SOCKET_MESSAGE
	while not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug('Message received in socket JEEDOM_SOCKET_MESSAGE')
		#message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
		message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode('utf-8'))
		logging.debug('Message received : %s',message)
		logging.debug('Message action received : %s',message['action'])
		if message['apikey'] != _apikey:
			logging.error('Invalid apikey from socket: ' + str(message))
			return
		try:
			if message['action'] == 'date-rpi':
				logging.debug('demande synchro date strompi : %s',message['action'])
				ser.write(str.encode('date-rpi'))
				time.sleep(0.1)
				ser.write(str.encode('\x0D'))
				data = ser.read(9999);
				date = int(data)
				strompi_year = date // 10000
				strompi_month = date % 10000 // 100
				strompi_day = date % 100
				time.sleep(0.1)
				ser.write(str.encode('time-rpi'))
				time.sleep(0.1)
				ser.write(str.encode('\x0D'))
				data = ser.read(9999);
				timevalue = int(data)
				strompi_hour = timevalue // 10000
				strompi_min = timevalue % 10000 // 100
				strompi_sec = timevalue % 100
				rpi_time = datetime.datetime.now().replace(microsecond=0)
				strompi_time = datetime.datetime(2000 + strompi_year, strompi_month, strompi_day, strompi_hour, strompi_min, strompi_sec, 0)
				command = 'set-time %02d %02d %02d' % (int(rpi_time.strftime('%H')),int(rpi_time.strftime('%M')),int(rpi_time.strftime('%S')))
				if rpi_time > strompi_time:
					ser.write(str.encode('set-date %02d %02d %02d %02d' % (int(rpi_time.strftime('%d')),int(rpi_time.strftime('%m')),int(rpi_time.strftime('%Y'))%100,int(rpi_time.isoweekday()))))
					time.sleep(0.3)
					ser.write(str.encode('\x0D'))
					time.sleep(1)
					ser.write(str.encode('set-clock %02d %02d %02d' % (int(rpi_time.strftime('%H')),int(rpi_time.strftime('%M')),int(rpi_time.strftime('%S')))))
					time.sleep(0.5)
					ser.write(str.encode('\x0D'))                
			elif message['action'] == "status-rpi":
				logging.debug('demande mise a jour strompi : %s',message['action'])
				read_strompi_status(message['eqlogic'])
			else:
				logging.error('Invalid action from socket')	
		except Exception as e:
			logging.error('Failed to perform an action: ' + str(e))

def listen():
	jeedom_socket.open()
	if ser.isOpen(): ser.close()
	ser.open()
	ser.write(str.encode('quit'))
	time.sleep(0.3)
	ser.write(str.encode('\x0D'))
	time.sleep(0.3)
	#ser.write(str.encode('date-rpi'))
	#time.sleep(0.3)
	#ser.write(str.encode('\x0D'))
	#data = ser.readline();
	#logging.debug('strompi <<< %s',data)
	#time.sleep(0.3)
	#ser.write(str.encode('time-rpi'))
	#time.sleep(0.3)
	#ser.write(str.encode('\x0D'))
	#data = ser.readline();
	#logging.debug('strompi <<< %s',data)
	try:
		while 1:
			time.sleep(0.3)
			read_strompi()
			read_socket()
            
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------

def read_strompi_status(eqlogic=0):
	logging.debug('execution de la commande status-rpi')
	ser.write(str.encode('\x0D'))
	time.sleep(0.3)
	ser.write(str.encode('status-rpi'))
	time.sleep(0.3)
	ser.write(str.encode('\x0D'))
	sp3_time = ser.readline(9999);
	sp3_date = ser.readline(9999);
	sp3_weekday = ser.readline(9999);
	sp3_modus = ser.readline(9999);
	sp3_alarm_enable = ser.readline(9999);
	sp3_alarm_mode = ser.readline(9999);
	sp3_alarm_hour = ser.readline(9999);
	sp3_alarm_min = ser.readline(9999);
	sp3_alarm_day = ser.readline(9999);
	sp3_alarm_month = ser.readline(9999);
	sp3_alarm_weekday = ser.readline(9999);
	sp3_alarmPoweroff = ser.readline(9999);
	sp3_alarm_hour_off = ser.readline(9999);
	sp3_alarm_min_off = ser.readline(9999);
	sp3_shutdown_enable = ser.readline(9999);
	sp3_shutdown_time = ser.readline(9999);
	sp3_warning_enable = ser.readline(9999);
	sp3_serialLessMode = ser.readline(9999);
	sp3_intervalAlarm = ser.readline(9999);
	sp3_intervalAlarmOnTime = ser.readline(9999);
	sp3_intervalAlarmOffTime = ser.readline(9999);
	sp3_batLevel_shutdown = ser.readline(9999);
	sp3_batLevel = ser.readline(9999);
	sp3_charging = ser.readline(9999);
	sp3_powerOnButton_enable = ser.readline(9999);
	sp3_powerOnButton_time = ser.readline(9999);
	sp3_powersave_enable = ser.readline(9999);
	sp3_poweroffMode = ser.readline(9999);
	sp3_poweroff_time_enable = ser.readline(9999);
	sp3_poweroff_time = ser.readline(9999);
	sp3_wakeupweekend_enable = ser.readline(9999);
	sp3_ADC_Wide = float(ser.readline(9999))/1000;
	sp3_ADC_BAT = float(ser.readline(9999))/1000;
	sp3_ADC_USB = float(ser.readline(9999))/1000;
	sp3_ADC_OUTPUT = float(ser.readline(9999))/1000;
	sp3_output_status = ser.readline(9999);
	sp3_powerfailure_counter = ser.readline(9999);
	sp3_firmwareVersion = ser.readline(9999);
	date = int(sp3_date)
	strompi_year = int(sp3_date) // 10000
	strompi_month = int(sp3_date) % 10000 // 100
	strompi_day = int(sp3_date) % 100
	strompi_hour = int(sp3_time) // 10000
	strompi_min = int(sp3_time) % 10000 // 100
	strompi_sec = int(sp3_time) % 100
	#logging.debug('eqlogic: ' + message['eqlogic'])
	#logging.debug('StromPi-Mode: ' + strompi_mode_converter((int(sp3_modus))))
	#logging.debug('StromPi-Output: ' + output_status_converter((int(sp3_output_status))))
	#logging.debug('Wide-Range-Inputvoltage: ' + str(sp3_ADC_Wide) + 'V')
	#_jeedomCom.send_change_immediate({'cmd' : 'update','StromPi-Mode' : '2'})
	DateTimeOutput = weekday_converter(int(sp3_weekday)) + ' ' + str(strompi_day).zfill(2) + '.' + str(strompi_month).zfill(2) + '.' + str(strompi_year).zfill(2) + ' ' + str(strompi_hour).zfill(2) + ':' + str(strompi_min).zfill(2) + ':' + str(strompi_sec).zfill(2)
	logging.debug('datetime: ' + DateTimeOutput)
	#jeedom_com.send_change_immediate({'StromPi-DateTimeOutput' : DateTimeOutput, 'eqlogic' : eqlogic})
	StrompiStatusOutput = ' ' + str(strompi_mode_converter((int(sp3_modus)))) + '|' + str(output_status_converter((int(sp3_output_status)))) + '|' + str(sp3_ADC_OUTPUT) + '|' + str(sp3_ADC_Wide) + '|' + str(sp3_ADC_USB) + '|' + str(sp3_ADC_BAT) + '|' + str(batterylevel_converter(int(sp3_batLevel),int(sp3_charging)))
	#StrompiStatusOutput = str(strompi_mode_converter((int(sp3_modus)))) + ' ' 
	logging.debug('Status Output: ' + StrompiStatusOutput)
	jeedom_com.send_change_immediate({'StromPi-StrompiStatusOutput' : DateTimeOutput + '|' + StrompiStatusOutput, 'eqlogic' : eqlogic})


def weekday_converter(argument):
    switcher = {
        1: 'lundi',
        2: 'Mardi',
        3: 'Mercredi',
        4: 'Jeudi',
        5: 'Vendredi',
        6: 'Samedi',
        7: 'Dimanche'
    }
    return switcher.get(argument, 'nothing')

def strompi_mode_converter(argument):
    switcher = {
        1: '1 = mUSB -> Wide',
        2: '2 = Wide -> mUSB',
        3: '3 = mUSB -> Battery',
        4: '4 = Wide -> Battery',
        5: "5 = mUSB -> Wide -> Battery",
        6: "6 = Wide -> mUSB -> Battery",
    }
    return switcher.get(argument, 'nothing')

def output_status_converter(argument):
    switcher = {
        0: 'Power-Off', #only for Debugging-Purposes
        1: 'mUSB',
        2: 'Wide',
        3: 'Battery',
    }
    return switcher.get(argument, 'nothing')

def batterylevel_converter(batterylevel,charging):

    if charging:
        switcher = {
            1: ' 10% charging',
            2: ' 25% charging',
            3: ' 50% charging',
            4: ' 100% charging',
        }
        return switcher.get(batterylevel, 'nothing')
    else:
        switcher = {
            1: ' [10%]',
            2: ' [25%]',
            3: ' [50%]',
            4: ' [100%]',
        }
        return switcher.get(batterylevel, 'nothing')



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
jeedom_com = jeedom_com(apikey=_apikey, url=_callback)

logging.info('Start demond')
logging.info('Log level: %s', _log_level)
logging.info('Socket port: %s', _socket_port)
logging.info('Socket host: %s', _socket_host)
logging.info('PID file: %s', _pidfile)
logging.info('Apikey: %s', _apikey)
logging.info('Callback: %s', _callback)
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


try:
	jeedom_utils.write_pid(str(_pidfile))
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception as e:
	logging.error('Fatal error: %s', e)
	logging.info(traceback.format_exc())
	ser.close()
	shutdown()