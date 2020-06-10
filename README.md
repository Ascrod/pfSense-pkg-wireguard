# pfSense-pkg-wireguard
pfSense integration for WireGuard.

## Build
The build process is similar to that of other FreeBSD and pfSense packages. You will need to set up a FreeBSD build environment and install or build `wireguard` and `wireguard-go` on it. Please check the [pfSense package development documentation](https://docs.netgate.com/pfsense/en/latest/development/developing-packages.html#testing-building-individual-packages) for more information.

## Installation
This package depends on the `wireguard` and `wireguard-go` ports for FreeBSD. First determine the [equivalent version of FreeBSD for your version of pfSense](https://docs.netgate.com/pfsense/en/latest/releases/versions-of-pfsense-and-freebsd.html). Download or build these packages for that version of FreeBSD, then manually install them using `pkg` before installing this package.

Look for latest package links of `wireguard` and `wireguard-go` in [FreeBSD 11 repository](https://pkg.freebsd.org/FreeBSD:11:amd64/latest/All/) or [FreeBSD 12 repository](https://pkg.freebsd.org/FreeBSD:12:amd64/latest/All/) depending on your pfSense version.

### Example
```bash
pkg add https://pkg.freebsd.org/FreeBSD:11:amd64/latest/All/wireguard-go-0.0.20200320.txz
pkg add https://pkg.freebsd.org/FreeBSD:11:amd64/latest/All/wireguard-1.0.20200513.txz
# pkg add https://pkg.freebsd.org/FreeBSD:11:amd64/latest/All/bash-5.0.17.txz # if dependency is missing
pkg add https://github.com/Ashus/pfSense-pkg-wireguard/releases/download/v1.0.1/pfSense-pkg-wireguard-1.0.1-freebsd11-amd64.txz
```

## Configuration
Configure an interface and any number of peers. Then go to the Assign Interfaces screen and create a new interface for `tunwg0`. Name it, enable it, and *don't touch any other settings.* Once the interface is up, you can create firewall rules for it, forward ports to it, and generally treat it the same as a physical interface. It should also persist across reboots.

If there is a need for more interfaces, add the `tunwg1.conf` or more files with incremental interface number to `/usr/local/etc/wireguard/`. Unfortunately those cannot be currently edited via GUI, and everytime you add more, you need to reinstall this package or wireguard service. Each time the service is reinstalled, all tunnels are detected from files again, so they could persist across reboots and could be reloaded from GUI all at once. 

For help with configuring WireGuard, please read the [official documentation](https://git.zx2c4.com/wireguard-tools/about/src/man/wg.8#CONFIGURATION%20FILE%20FORMAT). The [unofficial documentation](https://github.com/pirate/wireguard-docs) and examples may also be helpful.
