#!/usr/bin/python

import pygments.lexers

for fullname, names, exts, _ in pygments.lexers.get_all_lexers():
    for name in names:
        print(("%s|%s") % (name, fullname))
