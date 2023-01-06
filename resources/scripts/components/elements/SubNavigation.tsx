import styled from 'styled-components/macro';
import tw from 'twin.macro';

const SubNavigation = styled.div`
    ${tw`w-full bg-icelinenavbar-500 shadow overflow-x-auto`};

    & > div {
        ${tw`flex items-center text-sm mx-auto px-2`};
        max-width: 1200px;

        & > a,
        & > button,
        & > div {
            ${tw`inline-block py-3 px-4 text-neutral-200 no-underline whitespace-nowrap transition-all duration-150`};

            &:not(:first-of-type) {
                ${tw`ml-2`};
            }

            &:hover {
                ${tw`text-neutral-100`};
            }

            &:active,
            &:hover,
            &.active {
                box-shadow: inset 0 -2px #1572e8;
            }
        }
    }
`;

export default SubNavigation;
