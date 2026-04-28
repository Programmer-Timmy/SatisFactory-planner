import React from 'react';
import ReactDOM from 'react-dom/client';
import ProductionLineApp from './Components/ProductionLineApp';
import { ProductionSettingsProvider } from './Components/ProductionSettingsContext';

const root = ReactDOM.createRoot(document.getElementById('app-root') as HTMLElement);
root.render(
    React.createElement(ProductionSettingsProvider, null, React.createElement(ProductionLineApp))
);
