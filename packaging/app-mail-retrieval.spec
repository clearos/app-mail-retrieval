
Name: app-mail-retrieval
Epoch: 1
Version: 1.4.32
Release: 1%{dist}
Summary: Mail Retrieval
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The Mail Retrieval app can be used to fetch mail from external POP and IMAP servers.

%package core
Summary: Mail Retrieval - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-base >= 1:1.4.24
Requires: app-network-core >= 1:1.1.1
Requires: app-smtp-core >= 1:1.3.1
Requires: fetchmail

%description core
The Mail Retrieval app can be used to fetch mail from external POP and IMAP servers.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/mail_retrieval
cp -r * %{buildroot}/usr/clearos/apps/mail_retrieval/

install -d -m 0755 %{buildroot}/var/run/fetchmail
install -D -m 0600 packaging/fetchmail.conf %{buildroot}/etc/fetchmail
install -D -m 0755 packaging/fetchmail.init %{buildroot}/etc/rc.d/init.d/fetchmail
install -D -m 0644 packaging/fetchmail.php %{buildroot}/var/clearos/base/daemon/fetchmail.php

%post
logger -p local6.notice -t installer 'app-mail-retrieval - installing'

%post core
logger -p local6.notice -t installer 'app-mail-retrieval-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/mail_retrieval/deploy/install ] && /usr/clearos/apps/mail_retrieval/deploy/install
fi

[ -x /usr/clearos/apps/mail_retrieval/deploy/upgrade ] && /usr/clearos/apps/mail_retrieval/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail-retrieval - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-mail-retrieval-core - uninstalling'
    [ -x /usr/clearos/apps/mail_retrieval/deploy/uninstall ] && /usr/clearos/apps/mail_retrieval/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/mail_retrieval/controllers
/usr/clearos/apps/mail_retrieval/htdocs
/usr/clearos/apps/mail_retrieval/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/mail_retrieval/packaging
%exclude /usr/clearos/apps/mail_retrieval/tests
%dir /usr/clearos/apps/mail_retrieval
%dir %attr(0755,fetchmail,fetchmail) /var/run/fetchmail
/usr/clearos/apps/mail_retrieval/deploy
/usr/clearos/apps/mail_retrieval/language
/usr/clearos/apps/mail_retrieval/libraries
%attr(0600,fetchmail,root) %config(noreplace) /etc/fetchmail
/etc/rc.d/init.d/fetchmail
/var/clearos/base/daemon/fetchmail.php
