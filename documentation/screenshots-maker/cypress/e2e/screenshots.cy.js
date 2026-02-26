Cypress.Screenshot.defaults({
    capture: 'viewport',
})

Cypress.Commands.add('loginAsAdmin', () => {
    cy.env(['adminEmail', 'adminPassword']).then(env => {
        cy.visit('/login')
        cy.get('input[name="email"]').type(env.adminEmail);
        cy.get('input[name="password"]').type(env.adminPassword);
        cy.get('#loginform input[type="submit"]').click();
    });
});
Cypress.Commands.add('logout', () => {
    cy.visit('/logout');
});

describe('screenshots', () => {
    var strings;
    before(function() {
        cy.loginAsAdmin();
        const omekaLang = Cypress.expose('omekaLang');
        if (omekaLang) {
            cy.visit('/admin/setting');
            cy.get('#locale').select(omekaLang, { force: true });
            cy.get('#page-actions button').click();
        }
        cy.fixture('strings').then(_strings => { strings = _strings[omekaLang] ?? _strings[''] });
        cy.logout();
    });

    it('create source', () => {
        cy.loginAsAdmin();
        cy.visit('/admin/importit/source');
        cy.screenshot('images/source-browse-empty');

        cy.get('#page-actions a').click();
        cy.get('input[name="o:name"]').type(strings.sourceName);
        cy.get('select[name="o:type"]').select('server_side_mets');
        cy.screenshot('images/source-add-form');

        cy.get('#page-actions button').click();
        cy.get('input[name="o:settings[path]"]').type('/home/omeka/files');
        cy.screenshot('images/source-edit-server-side-mets');

        cy.get('#page-actions button').click();
        cy.screenshot('images/source-browse-after-edit');

        cy.get('[data-cy="start-import"]').click();
        cy.screenshot('images/source-import-form');
    });
})
