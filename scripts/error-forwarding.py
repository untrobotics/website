#!/usr/bin/env python3

# sample incrontab entry:
# /var/log/apache2/untrobotics/error.log IN_MODIFY /www/error-forwarding.py $$ $@ $# $% $&

# known bugs/issues:
# when the logs file is rotated, the length comparisons will not produce a result and we will lose the first line of the newly rotated log file (although that is likely just an informational line anyway)

import os
import sys
import subprocess
from pathlib import Path
from shutil import copyfile

try:
	# get the directory that this file is in
	dir_path = os.path.dirname(os.path.realpath(__file__))

	# get the paths of the new and prev log files
	new_log_path = Path(dir_path) / Path("tmp/untrobotics-error.log")
	prev_log_path = Path(dir_path) / Path("tmp/prev-untrobotics-error.log")

	if not new_log_path.is_file():
		new_log_path.touch()

	# copy the currently stored path to prev-{file}
	copyfile(new_log_path, prev_log_path);
	# get the current copy of the error log file and store it here
	copyfile(sys.argv[2], new_log_path);

	# read the actual contents of both stored log files
	prev_log = open(prev_log_path).readlines()
	current_log = open(new_log_path).readlines()

	# calculate the lengths of the content
	prev_len = len(prev_log)
	current_len = len(current_log)

	# call the PHP script which contains the discord bot send message function
	subprocess.run(
		[
			"/usr/bin/php",
			Path(dir_path) / Path("../api/discord/bots/admin-cli.php"),
			''.join(current_log[prev_len:current_len]),
			str(prev_len),
			str(current_len)
		]
	);
except Exception as e:
	subprocess.run(
                [
                        "/usr/bin/php",
                        Path(dir_path) / Path("../api/discord/bots/admin-cli.php"),
			"Error occurred while attempting to retrieve error logs:\n" + str(e),
			str(prev_len),
			str(current_len)
                ]
        );
