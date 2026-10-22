export function convertSizeToBytes(size) {
    if (size.indexOf('GB') !== -1) {
        return parseInt(size.replace('GB', '')) * 1024 * 1024 * 1024;
    }

    if (size.indexOf('MB') !== -1) {
        return parseInt(size.replace('MB', '')) * 1024 * 1024;
    }

    return 1024 * 1024 * 1024;
}
