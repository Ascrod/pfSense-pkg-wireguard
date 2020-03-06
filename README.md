# pfSense-pkg-wireguard
pfSense integration for WireGuard.

## Build
The build process is similar to that of other FreeBSD and pfSense packages. You will need to set up a FreeBSD build environment and install or build `wireguard` and `wireguard-go` on it. Please check the [pfSense package development documentation](https://docs.netgate.com/pfsense/en/latest/development/developing-packages.html#testing-building-individual-packages) for more information.

## Installation
This package depends on the `wireguard` and `wireguard-go` ports for FreeBSD. First determine the [equivalent version of FreeBSD for your version of pfSense](https://docs.netgate.com/pfsense/en/latest/releases/versions-of-pfsense-and-freebsd.html). Download or build these packages for that version of FreeBSD, then manually install them using `pkg` before installing this package.

## Configuration
Configure an interface and any number of peers. Then go to the Assign Interfaces screen and create a new interface for `tunwg0`. Name it, enable it, and *don't touch any other settings.* Once the interface is up, you can create firewall rules for it, forward ports to it, and generally treat it the same as a physical interface. It should also persist across reboots.

For help with configuring WireGuard, please read the [official documentation](https://git.zx2c4.com/wireguard-tools/about/src/man/wg.8#CONFIGURATION%20FILE%20FORMAT). The [unofficial documentation](https://github.com/pirate/wireguard-docs) and examples may also be helpful.
