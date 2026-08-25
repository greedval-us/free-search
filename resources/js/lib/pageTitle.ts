export const formatPageTitle = (title: string, appName: string): string => {
    const normalizedTitle = title.trim();
    const normalizedAppName = appName.trim();

    if (normalizedTitle === '') {
        return normalizedAppName;
    }

    if (
        normalizedAppName !== '' &&
        normalizedTitle
            .toLocaleLowerCase()
            .includes(normalizedAppName.toLocaleLowerCase())
    ) {
        return normalizedTitle;
    }

    return normalizedAppName === ''
        ? normalizedTitle
        : `${normalizedTitle} - ${normalizedAppName}`;
};
