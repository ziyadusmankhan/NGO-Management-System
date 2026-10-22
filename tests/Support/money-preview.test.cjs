const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const context = {window: {}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/money.js'), 'utf8'), context);
const money = context.window.FoundationMoney;

assert.equal(money.minor('10.25'), 1025n);
assert.equal(money.formatMinor(money.minor('500.00') - money.minor('10.25')), '489.75');
assert.equal(money.formatMinor(money.minor('0.10') + money.minor('0.20')), '0.30');
assert.equal(money.format('999999999999.99'), '999,999,999,999.99');
assert.equal(money.formatMinor(money.minor('999999999999.99') - money.minor('0.01')), '999,999,999,999.98');
assert.equal(money.format('-0.01'), '-0.01');
assert.equal(money.format('500'), '500.00');
assert.equal(money.format(''), '0.00');
assert.equal(money.minor('0.005'), 0n);
assert.equal(money.minor('1e2'), 0n);
console.log('Money preview checks passed (10 assertions).');
