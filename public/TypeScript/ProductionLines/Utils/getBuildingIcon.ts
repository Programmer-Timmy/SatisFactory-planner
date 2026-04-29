const getBuildingIcon = (className?: string) => {
    if (!className) return '';
    return `/image/items/${className.toLowerCase().replaceAll('_', '-').replace(/build/gi, 'desc')}_256.png`;
};

export default getBuildingIcon;